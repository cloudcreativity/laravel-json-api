<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Exceptions\ExceptionParserInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderParametersParserInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersParserInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\ExceptionParser;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Headers\HeaderParametersParser;
use CloudCreativity\LaravelJsonApi\Http\Middleware\Authorize;
use CloudCreativity\LaravelJsonApi\Http\Middleware\BootJsonApi;
use CloudCreativity\LaravelJsonApi\Http\Middleware\NegotiateContent;
use CloudCreativity\LaravelJsonApi\Http\Query\QueryParametersParser;
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
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

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
                __DIR__ . '/../lang' => $this->app->langPath() . '/vendor/jsonapi',
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
        $this->loadTranslationsFrom(__DIR__ . '/../lang', 'jsonapi');
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
                app(QueryParametersInterface::class)
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
     * @return void
     */
    protected function bindNeomerx(): void
    {
        $this->app->singleton(Factory::class);
        $this->app->alias(Factory::class, FactoryInterface::class);
        $this->app->bind(
            \Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersParserInterface::class,
            \Neomerx\JsonApi\Http\Headers\HeaderParametersParser::class,
        );
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
    protected function bindInboundRequest(): void
    {
        $this->app->singleton(Route::class, function (Application $app) {
            return new Route(
                $app->make(ResolverInterface::class),
                $app->make('router')->current()
            );
        });

        $this->app->singleton(StoreInterface::class, function () {
            return json_api()->getStore();
        });

        $this->app->singleton(ResolverInterface::class, function () {
            return json_api()->getResolver();
        });

        $this->app->singleton(ContainerInterface::class, function () {
            return json_api()->getContainer();
        });

        $this->app->bind(HeaderParametersParserInterface::class, HeaderParametersParser::class);

        $this->app->scoped(HeaderParametersInterface::class, function (Application $app) {
            /** @var HeaderParametersParserInterface $parser */
            $parser = $app->make(HeaderParametersParserInterface::class);
            /** @var ServerRequestInterface $serverRequest */
            $serverRequest = $app->make(ServerRequestInterface::class);
            return $parser->parse($serverRequest, http_contains_body($serverRequest));
        });

        $this->app->scoped(QueryParametersInterface::class, function (Application $app) {
            /** @var QueryParametersParserInterface $parser */
            $parser = $app->make(QueryParametersParserInterface::class);

            return $parser->parseQueryParameters(
                request()->query()
            );
        });

        $this->app->scoped(QueryParametersParserInterface::class, QueryParametersParser::class);
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
