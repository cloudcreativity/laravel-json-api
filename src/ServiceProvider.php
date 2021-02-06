<?php
/*
 * Copyright 2021 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi;

use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Exceptions\ExceptionParserInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\ExceptionParser;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Middleware\Authorize;
use CloudCreativity\LaravelJsonApi\Http\Middleware\BootJsonApi;
use CloudCreativity\LaravelJsonApi\Http\Middleware\NegotiateContent;
use CloudCreativity\LaravelJsonApi\Queue\UpdateClientProcess;
use CloudCreativity\LaravelJsonApi\Routing\JsonApiRegistrar;
use CloudCreativity\LaravelJsonApi\Routing\Route;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use CloudCreativity\LaravelJsonApi\View\Renderer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Neomerx\JsonApi\Contracts\Document\DocumentFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Handlers\HandlerFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parser\ParserFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Stack\StackFactoryInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersParserInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ServiceProvider
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ServiceProvider extends BaseServiceProvider
{

    /**
     * @param Router $router
     */
    public function boot(Router $router)
    {
        $this->bootMiddleware($router);
        $this->bootResponseMacro();
        $this->bootBladeDirectives();
        $this->bootTranslations();

        if (LaravelJsonApi::$queueBindings) {
            Queue::after(UpdateClientProcess::class);
            Queue::failing(UpdateClientProcess::class);
        }

        if ($this->app->runningInConsole()) {
            $this->bootMigrations();

            $this->publishes([
                __DIR__ . '/../database/migrations' => database_path('migrations'),
            ], 'json-api:migrations');

            $this->publishes([
                __DIR__ . '/../resources/lang' => resource_path('lang/vendor/jsonapi'),
            ], 'json-api:translations');

            $this->commands([
                Console\Commands\MakeAdapter::class,
                Console\Commands\MakeApi::class,
                Console\Commands\MakeAuthorizer::class,
                Console\Commands\MakeContentNegotiator::class,
                Console\Commands\MakeResource::class,
                Console\Commands\MakeSchema::class,
                Console\Commands\MakeValidators::class,
            ]);
        }
    }

    /**
     * Register JSON API services.
     *
     * @return void
     */
    public function register()
    {
        $this->bindNeomerx();
        $this->bindService();
        $this->bindInboundRequest();
        $this->bindRouteRegistrar();
        $this->bindApiRepository();
        $this->bindExceptionParser();
        $this->bindRenderer();
    }

    /**
     * Register package middleware.
     *
     * @param Router $router
     */
    protected function bootMiddleware(Router $router)
    {
        $router->aliasMiddleware('json-api', BootJsonApi::class);
        $router->aliasMiddleware('json-api.content', NegotiateContent::class);
        $router->aliasMiddleware('json-api.auth', Authorize::class);
    }

    /**
     * Register package translations.
     *
     * @return void
     */
    protected function bootTranslations()
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'jsonapi');
    }

    /**
     * Register a response macro.
     *
     * @return void
     */
    protected function bootResponseMacro()
    {
        Response::macro('jsonApi', function ($api = null) {
            return json_api($api)->getResponses()->withEncodingParameters(
                app(EncodingParametersInterface::class)
            );
        });
    }

    /**
     * Register Blade directives.
     */
    protected function bootBladeDirectives()
    {
        /** @var BladeCompiler $compiler */
        $compiler = $this->app->make(BladeCompiler::class);
        $compiler->directive('jsonapi', Renderer::class . '::compileWith');
        $compiler->directive('encode', Renderer::class . '::compileEncode');
    }

    /**
     * Register package migrations.
     *
     * @return void
     */
    protected function bootMigrations()
    {
        if (LaravelJsonApi::$runMigrations) {
            $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        }
    }

    /**
     * Bind parts of the neomerx/json-api dependency into the service container.
     *
     * For this Laravel JSON API package, we use our extended JSON API factory.
     * This ensures that we can override any parts of the Neomerx JSON API package
     * that we want.
     *
     * As the Neomerx package splits the factories into multiple interfaces, we
     * also register aliases for each of the factory interfaces.
     *
     * The Neomerx package allows a logger to be injected into the factory. This
     * enables the Neomerx package to log messages. When creating the factory, we
     * therefore set the logger as our application's logger.
     *
     * @return void
     */
    protected function bindNeomerx()
    {
        $this->app->singleton(Factory::class, function (Application $app) {
            $factory = new Factory($app);
            $factory->setLogger($app->make(LoggerInterface::class));
            return $factory;
        });

        $this->app->alias(Factory::class, FactoryInterface::class);
        $this->app->alias(Factory::class, DocumentFactoryInterface::class);
        $this->app->alias(Factory::class, HandlerFactoryInterface::class);
        $this->app->alias(Factory::class, HttpFactoryInterface::class);
        $this->app->alias(Factory::class, ParserFactoryInterface::class);
        $this->app->alias(Factory::class, SchemaFactoryInterface::class);
        $this->app->alias(Factory::class, StackFactoryInterface::class);
    }

    /**
     * Bind the JSON API service as a singleton.
     */
    protected function bindService()
    {
        $this->app->singleton(JsonApiService::class);
        $this->app->alias(JsonApiService::class, 'json-api');
        $this->app->alias(JsonApiService::class, 'json-api.service');
    }

    /**
     * Bind an alias for the route registrar.
     */
    protected function bindRouteRegistrar()
    {
        $this->app->alias(JsonApiRegistrar::class, 'json-api.registrar');
    }

    /**
     * Bind the inbound request services so they can be type-hinted in controllers and authorizers.
     *
     * @return void
     */
    protected function bindInboundRequest()
    {
        $this->app->singleton(Route::class, function (Application $app) {
            return new Route(
                $app->make(ResolverInterface::class),
                $app->make('router')->current()
            );
        });

        $this->app->bind(StoreInterface::class, function () {
            return json_api()->getStore();
        });

        $this->app->bind(ResolverInterface::class, function () {
            return json_api()->getResolver();
        });

        $this->app->bind(ContainerInterface::class, function () {
            return json_api()->getContainer();
        });

        $this->app->singleton(HeaderParametersInterface::class, function (Application $app) {
            /** @var HeaderParametersParserInterface $parser */
            $parser = $app->make(HttpFactoryInterface::class)->createHeaderParametersParser();
            /** @var ServerRequestInterface $serverRequest */
            $serverRequest = $app->make(ServerRequestInterface::class);

            return $parser->parse($serverRequest, http_contains_body($serverRequest));
        });

        $this->app->singleton(EncodingParametersInterface::class, function (Application $app) {
            /** @var QueryParametersParserInterface $parser */
            $parser = $app->make(HttpFactoryInterface::class)->createQueryParametersParser();

            return $parser->parseQueryParameters(
                request()->query()
            );
        });
    }

    /**
     * Bind the API repository as a singleton.
     */
    protected function bindApiRepository()
    {
        $this->app->singleton(Repository::class);
    }

    /**
     * Bind the exception parser into the service container.
     */
    protected function bindExceptionParser()
    {
        $this->app->singleton(ExceptionParserInterface::class, ExceptionParser::class);
        $this->app->alias(ExceptionParserInterface::class, 'json-api.exceptions');
    }

    /**
     * Bind the view renderer into the service container.
     */
    protected function bindRenderer()
    {
        $this->app->singleton(Renderer::class);
        $this->app->alias(Renderer::class, 'json-api.renderer');
    }

    /**
     * @param $key
     * @param $default
     * @return array
     */
    protected function getConfig($key, $default = null)
    {
        $key = sprintf('%s.%s', 'json-api', $key);

        return config($key, $default);
    }

    /**
     * @return array
     */
    protected function getErrorConfig()
    {
        return (array) config('json-api-errors');
    }

}
