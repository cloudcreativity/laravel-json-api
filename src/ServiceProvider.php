<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

use CloudCreativity\JsonApi\Contracts\Exceptions\ExceptionParserInterface;
use CloudCreativity\JsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Console\Commands;
use CloudCreativity\LaravelJsonApi\Exceptions\ExceptionParser;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Middleware\AuthorizeRequest;
use CloudCreativity\LaravelJsonApi\Http\Middleware\BootJsonApi;
use CloudCreativity\LaravelJsonApi\Http\Middleware\SubstituteBindings;
use CloudCreativity\LaravelJsonApi\Http\Middleware\ValidateRequest;
use CloudCreativity\LaravelJsonApi\Http\Requests\RequestInterpreter;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use CloudCreativity\LaravelJsonApi\View\Renderer;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use Neomerx\JsonApi\Contracts\Document\DocumentFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Handlers\HandlerFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parser\ParserFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Stack\StackFactoryInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface as NeomerxFactoryInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Psr\Log\LoggerInterface;

/**
 * Class ServiceProvider
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ServiceProvider extends BaseServiceProvider
{

    /**
     * @var array
     */
    protected $generatorCommands = [
        Commands\MakeAdapterCommand::class,
        Commands\MakeApiCommand::class,
        Commands\MakeHydratorCommand::class,
        Commands\MakeResourceCommand::class,
        Commands\MakeSchemaCommand::class,
        Commands\MakeValidatorsCommand::class,
    ];

    /**
     * @param Router $router
     */
    public function boot(Router $router)
    {
        $this->bootMiddleware($router);
        $this->bootResponseMacro();
        $this->bootBladeDirectives();
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
        $this->bindRequestInterpreter();
        $this->bindInboundRequest();
        $this->bindRouteRegistrar();
        $this->bindApiRepository();
        $this->bindExceptionParser();
        $this->bindRenderer();
        $this->registerArtisanCommands();
        $this->mergePackageConfig();
    }

    /**
     * Register package middleware.
     *
     * @param Router $router
     */
    protected function bootMiddleware(Router $router)
    {
        $router->aliasMiddleware('json-api', BootJsonApi::class);
        $router->aliasMiddleware('json-api.authorize', AuthorizeRequest::class);
        $router->aliasMiddleware('json-api.validate', ValidateRequest::class);
        $router->aliasMiddleware('json-api.bindings', SubstituteBindings::class);
    }

    /**
     * Register a response macro.
     *
     * @return void
     */
    protected function bootResponseMacro()
    {
        Response::macro('jsonApi', function ($api = null) {
            return Responses::create($api);
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
        $this->app->alias(Factory::class, NeomerxFactoryInterface::class);
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
        $this->app->alias(ResourceRegistrar::class, 'json-api.registrar');
    }

    /**
     * Bind a request interpreter into the container.
     */
    protected function bindRequestInterpreter()
    {
        $this->app->singleton(RequestInterpreterInterface::class, RequestInterpreter::class);
    }

    /**
     * Bind the inbound request services so they can be type-hinted in controllers and authorizers.
     *
     * @return void
     */
    protected function bindInboundRequest()
    {
        $this->app->bind(StoreInterface::class, function () {
            return json_api()->getStore();
        });

        $this->app->bind(ErrorRepositoryInterface::class, function (Application $app) {
            return json_api()->getErrors();
        });

        $this->app->bind(DocumentInterface::class, function () {
            if (!$request = json_api_request()) {
                throw new RuntimeException('No inbound JSON API request.');
            }

            if (!$document = $request->getDocument()) {
                throw new RuntimeException('No request document on inbound JSON API request.');
            }

            return $document;
        });

        $this->app->bind(ResourceObjectInterface::class, function (Application $app) {
            return $app->make(DocumentInterface::class)->getResource();
        });

        $this->app->bind(RelationshipInterface::class, function (Application $app) {
            return $app->make(DocumentInterface::class)->getRelationship();
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
     * Register generator commands with artisan
     */
    protected function registerArtisanCommands()
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->generatorCommands);
        }
    }

    /**
     * Merge default package config.
     */
    protected function mergePackageConfig()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/json-api-errors.php', 'json-api-errors');
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
