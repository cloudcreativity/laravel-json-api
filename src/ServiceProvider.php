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
use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use CloudCreativity\JsonApi\Contracts\Http\HttpServiceInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Contracts\Http\Responses\ResponseFactoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Http\Responses\ResponseFactory;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Console\Commands;
use CloudCreativity\LaravelJsonApi\Contracts\Document\LinkFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Document\LinkFactory;
use CloudCreativity\LaravelJsonApi\Exceptions\ExceptionParser;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Middleware\AuthorizeRequest;
use CloudCreativity\LaravelJsonApi\Http\Middleware\BootJsonApi;
use CloudCreativity\LaravelJsonApi\Http\Middleware\ValidateRequest;
use CloudCreativity\LaravelJsonApi\Http\Requests\RequestInterpreter;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorErrorFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Neomerx\JsonApi\Contracts\Document\DocumentFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Handlers\HandlerFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parser\ParserFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Stack\StackFactoryInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface as NeomerxFactoryInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;
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
     * @var bool
     */
    protected $defer = false;

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
     * @param ResponseFactoryContract $responses
     */
    public function boot(
        Router $router,
        ResponseFactoryContract $responses
    ) {
        $this->bootMiddleware($router);
        $this->bootResponseMacro($responses);
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
        $this->bindApiRepository();
        $this->bindApiServices();
        $this->bindExceptionParser();
        $this->bindResponses();
        $this->bindLinkFactory();
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
        /** Laravel 5.4 */
        if (method_exists($router, 'aliasMiddleware')) {
            $router->aliasMiddleware('json-api', BootJsonApi::class);
            $router->aliasMiddleware('json-api.authorize', AuthorizeRequest::class);
            $router->aliasMiddleware('json-api.validate', ValidateRequest::class);
        } /** Laravel 5.1|5.2|5.3 */
        else {
            $router->middleware('json-api', BootJsonApi::class);
            $router->middleware('json-api.authorize', AuthorizeRequest::class);
            $router->middleware('json-api.validate', ValidateRequest::class);
        }
    }

    /**
     * Register a response macro.
     *
     * @param ResponseFactoryContract $responses
     */
    protected function bootResponseMacro(ResponseFactoryContract $responses)
    {
        if (method_exists($responses, 'macro')) {
            $responses->macro('jsonApi', function () {
                return app(ResponseFactoryInterface::class);
            });
        }
    }

    /**
     * Bind parts of the neomerx/json-api dependency into the service container.
     *
     * For this Laravel JSON API package, we use our extended JSON API factory.
     * This ensures that we can override any parts of the Neomerx JSON API pacakge
     * that we want.
     *
     * As the Neomerx package splits the factories into multiple interfaces, we
     * also register aliases for each of the factory interfaces.
     *
     * The Neomerx package allows a logger to be injected into the factory. This
     * enables the Neomerx package to log messages. When creating the factory, we
     * therefore set the logger as our application's logger.
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
     * Bind an alias for the JSON API service.
     */
    protected function bindService()
    {
        $this->app->singleton(JsonApiService::class);
        $this->app->alias(JsonApiService::class, HttpServiceInterface::class);
        $this->app->alias(JsonApiService::class, 'json-api.service');
    }

    /**
     * Bind a request interpreter into the container.
     */
    protected function bindRequestInterpreter()
    {
        $this->app->singleton(RequestInterpreterInterface::class, RequestInterpreter::class);
    }

    /**
     * Bind services that are resolved from the active API.
     */
    protected function bindApiServices()
    {
        $this->app->bind(StoreInterface::class, function (Application $app) {
            /** @var ApiInterface $api */
            $api = $app->make(ApiInterface::class);
            return $api->getStore();
        });

        $this->app->bind(ErrorRepositoryInterface::class, function (Application $app) {
            /** @var ApiInterface $api */
            $api = $app->make(ApiInterface::class);
            return $api->getErrors();
        });

        $this->app->bind(ValidatorErrorFactoryInterface::class, function (Application $app) {
            /** @var Factory $factory */
            $factory = $app->make(Factory::class);
            $errors = $app->make(ErrorRepositoryInterface::class);

            return new ValidatorErrorFactory($errors);
        });

        $this->app->alias(
            ValidatorErrorFactoryInterface::class,
            \CloudCreativity\JsonApi\Contracts\Validators\ValidatorErrorFactoryInterface::class
        );
    }

    /**
     * Bind the API repository as a singleton.
     */
    protected function bindApiRepository()
    {
        $this->app->singleton(Repository::class);
    }

    /**
     * Bind the responses instance into the service container.
     */
    protected function bindResponses()
    {
        $this->app->singleton(ResponsesInterface::class, Responses::class);
        $this->app->singleton(ResponseFactoryInterface::class, ResponseFactory::class);
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
     * Bind the link factory into the service container.
     */
    protected function bindLinkFactory()
    {
        $this->app->singleton(LinkFactoryInterface::class, LinkFactory::class);
        $this->app->alias(LinkFactoryInterface::class, 'json-api.links');
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
