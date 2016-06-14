<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

use CloudCreativity\JsonApi\Contracts\Http\ApiFactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\SchemasRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Stdlib\ConfigurableInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Http\ApiFactory;
use CloudCreativity\JsonApi\Http\ContentNegotiator;
use CloudCreativity\JsonApi\Repositories\CodecMatcherRepository;
use CloudCreativity\JsonApi\Repositories\ErrorRepository;
use CloudCreativity\JsonApi\Repositories\SchemasRepository;
use CloudCreativity\JsonApi\Store\Store;
use CloudCreativity\LaravelJsonApi\Adapters\EloquentAdapter;
use CloudCreativity\LaravelJsonApi\Contracts\Document\LinkFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PageParameterHandlerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PaginatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Document\LinkFactory;
use CloudCreativity\LaravelJsonApi\Http\Middleware\BootJsonApi;
use CloudCreativity\LaravelJsonApi\Http\Responses\ResponseFactory;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Pagination\PageParameterHandler;
use CloudCreativity\LaravelJsonApi\Pagination\Paginator;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorErrorFactory;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorFactory;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Neomerx\JsonApi\Factories\Factory;

/**
 * Class ServiceProvider
 * @package CloudCreativity\LaravelJsonApi
 */
class ServiceProvider extends BaseServiceProvider
{

    /**
     * @var bool
     */
    protected $defer = false;

    /**
     * @param Router $router
     * @param ResponseFactoryContract $responses
     */
    public function boot(
        Router $router,
        ResponseFactoryContract $responses
    ) {
        $this->bootPublishing();
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
        $this->bindApiFactory();
        $this->bindCodecMatcherRepository();
        $this->bindSchemaRepository();
        $this->bindErrorRepository();
        $this->bindContentNegotiator();
        $this->bindResponses();
        $this->bindValidatorFactory();
        $this->bindValidatorErrorFactory();
        $this->bindStore();
        $this->bindEloquentAdapter();
        $this->bindLinkFactory();
        $this->bindPageParameterHandler();
        $this->bindPaginator();
    }

    /**
     * Register the configuration that this package publishes.
     *
     * @return void
     */
    protected function bootPublishing()
    {
        $this->publishes([
            __DIR__ . '/../config/json-api.php' => config_path('json-api.php'),
        ], 'config');

        $this->publishes([
            base_path('vendor/cloudcreativity/json-api/config/validation.php') => config_path('json-api-errors.php'),
        ], 'validation');
    }

    /**
     * Register package middleware.
     *
     * @param Router $router
     */
    protected function bootMiddleware(Router $router)
    {
        $router->middleware('json-api', BootJsonApi::class);
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
                return app(ResponseFactory::class);
            });
        }
    }

    /**
     * Bind parts of the neomerx/json-api dependency into the service container.
     */
    protected function bindNeomerx()
    {
        $this->app->singleton(FactoryInterface::class, Factory::class);
        $this->app->singleton(SchemaFactoryInterface::class, FactoryInterface::class);
        $this->app->singleton(HttpFactoryInterface::class, FactoryInterface::class);
    }

    /**
     * Bind an alias for the JSON API service.
     */
    protected function bindService()
    {
        $this->app->alias(JsonApiService::class, 'json-api.service');
    }

    /**
     * Bind the API factory into the service container.
     */
    protected function bindApiFactory()
    {
        $this->app->singleton(ApiFactoryInterface::class, ApiFactory::class);
    }

    /**
     * Bind the codec matcher repository into the service container.
     */
    protected function bindCodecMatcherRepository()
    {
        $this->app->singleton(CodecMatcherRepositoryInterface::class, CodecMatcherRepository::class);
        $this->app->resolving(CodecMatcherRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure((array) $this->getConfig('codec-matcher', []));
        });
    }

    /**
     * Bind the schema repository into the service container.
     */
    protected function bindSchemasRepository()
    {
        $this->app->singleton(SchemasRepositoryInterface::class, SchemasRepository::class);
        $this->app->resolving(SchemasRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure((array) $this->getConfig('schemas', []));
        });
    }

    /**
     * Bind the schema repository into the service container.
     */
    protected function bindSchemaRepository()
    {
        $this->app->singleton(SchemasRepositoryInterface::class, SchemasRepository::class);
        $this->app->resolving(SchemasRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure((array) $this->getConfig('schemas', []));
        });
    }

    /**
     * Bind the content negotiator into the service container.
     */
    protected function bindContentNegotiator()
    {
        $this->app->singleton(ContentNegotiatorInterface::class, ContentNegotiator::class);
    }


    /**
     * Bind the responses instance into the service container.
     */
    protected function bindResponses()
    {
        $this->app->singleton(ResponsesInterface::class, Responses::class);
    }

    /**
     * Bind the validator factory into the service container.
     */
    protected function bindValidatorFactory()
    {
        $this->app->singleton(ValidatorFactoryInterface::class, ValidatorFactory::class);
    }

    /**
     * Bind the validator error factory into the service container.
     */
    protected function bindValidatorErrorFactory()
    {
        $this->app->singleton(ValidatorErrorFactoryInterface::class, ValidatorErrorFactory::class);
    }

    /**
     * Bind the error repository into the service container.
     */
    protected function bindErrorRepository()
    {
        $this->app->singleton(ErrorRepositoryInterface::class, function () {
            return new ErrorRepository($this->getErrorConfig());
        });
    }

    /**
     * Bind the store into the service container.
     */
    protected function bindStore()
    {
        $this->app->singleton(['json-api.store' => StoreInterface::class], Store::class);
    }

    /**
     * Bind the Eloquent adapter into the service container.
     */
    protected function bindEloquentAdapter()
    {
        $this->app->singleton(EloquentAdapter::class, function () {
            $map = (array) $this->getConfig('eloquent-adapter.map');
            $columns = (array) $this->getConfig('eloquent-adapter.columns');

            return new EloquentAdapter($map, $columns);
        });

        $this->app->resolving(StoreInterface::class, function (StoreInterface $store) {
            /** @var EloquentAdapter $adapter */
            $adapter = $this->app->make(EloquentAdapter::class);
            $store->register($adapter);
        });
    }

    /**
     * Bind the link factory into the service container.
     */
    protected function bindLinkFactory()
    {
        $this->app->singleton(['json-api.links' => LinkFactoryInterface::class], LinkFactory::class);
    }

    /**
     * Bind the page parameter handler into the service container.
     */
    protected function bindPageParameterHandler()
    {
        $this->app->singleton(['json-api.page' => PageParameterHandlerInterface::class], PageParameterHandler::class);
    }

    /**
     * Bind the paginator into the service container.
     */
    protected function bindPaginator()
    {
        $this->app->singleton(['json-api.paginator' => PaginatorInterface::class], Paginator::class);
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
