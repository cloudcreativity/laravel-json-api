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

use CloudCreativity\JsonApi\Contracts\Exceptions\ExceptionParserInterface;
use CloudCreativity\JsonApi\Contracts\Http\ApiFactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\HttpServiceInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestFactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Contracts\Http\Responses\ResponseFactoryInterface;
use CloudCreativity\JsonApi\Contracts\Pagination\PaginatorInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\SchemasRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Contracts\Utils\ConfigurableInterface;
use CloudCreativity\JsonApi\Contracts\Utils\ReplacerInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorFactoryInterface as BaseValidatorFactoryInterface;
use CloudCreativity\JsonApi\Factories\Factory;
use CloudCreativity\JsonApi\Http\ApiFactory;
use CloudCreativity\JsonApi\Http\Requests\RequestFactory;
use CloudCreativity\JsonApi\Http\Responses\ResponseFactory;
use CloudCreativity\JsonApi\Pagination\Paginator;
use CloudCreativity\JsonApi\Repositories\CodecMatcherRepository;
use CloudCreativity\JsonApi\Repositories\ErrorRepository;
use CloudCreativity\JsonApi\Store\Store;
use CloudCreativity\JsonApi\Utils\Replacer;
use CloudCreativity\LaravelJsonApi\Adapters\EloquentAdapter;
use CloudCreativity\LaravelJsonApi\Console\Commands\HydratorMakeCommand;
use CloudCreativity\LaravelJsonApi\Console\Commands\RequestMakeCommand;
use CloudCreativity\LaravelJsonApi\Console\Commands\ResourceMakeCommand;
use CloudCreativity\LaravelJsonApi\Console\Commands\SchemaMakeCommand;
use CloudCreativity\LaravelJsonApi\Console\Commands\SearchMakeCommand;
use CloudCreativity\LaravelJsonApi\Console\Commands\ValidatorsMakeCommand;
use CloudCreativity\LaravelJsonApi\Contracts\Document\LinkFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Document\LinkFactory;
use CloudCreativity\LaravelJsonApi\Exceptions\ExceptionParser;
use CloudCreativity\LaravelJsonApi\Http\Middleware\BootJsonApi;
use CloudCreativity\LaravelJsonApi\Http\Middleware\HandleRequest;
use CloudCreativity\LaravelJsonApi\Http\Requests\RequestInterpreter;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Pagination\Page;
use CloudCreativity\LaravelJsonApi\Repositories\EloquentSchemasRepository;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorErrorFactory;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorFactory;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Neomerx\JsonApi\Contracts\Document\DocumentFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Handlers\HandlerFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parser\ParserFactoryInterface;
use Neomerx\JsonApi\Contracts\Encoder\Stack\StackFactoryInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use App;

/**
 * Class ServiceProvider
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ServiceProvider extends BaseServiceProvider
{
    const JSON_API_SCHEMA = 'JSON_API_SCHEMA';

    /**
     * @var bool
     */
    protected $defer = false;

    /**
     * @var array
     */
    protected $generatorCommands = [
        HydratorMakeCommand::class,
        RequestMakeCommand::class,
        ResourceMakeCommand::class,
        SchemaMakeCommand::class,
        SearchMakeCommand::class,
        ValidatorsMakeCommand::class,
    ];

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
        $this->bindExceptionParser();
        $this->bindRequestFactory();
        $this->bindResponses();
        $this->bindValidatorFactory();
        $this->bindValidatorErrorFactory();
        $this->bindStore();
        $this->bindEloquentAdapter();
        $this->bindStoreAdapters();
        $this->bindLinkFactory();
        $this->bindPagination();

        $this->registerArtisanCommands();
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
            __DIR__ . '/../config/json-api-errors.php' => config_path('json-api-errors.php'),
        ], 'errors');
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
            $router->aliasMiddleware('json-api.request', HandleRequest::class);
        } /** Laravel 5.1|5.2|5.3 */
        else {
            $router->middleware('json-api', BootJsonApi::class);
            $router->middleware('json-api.request', HandleRequest::class);
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
            $factory = new Factory();
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
     * Bind an alias for the JSON API service.
     */
    protected function bindService()
    {
        $this->app->singleton(JsonApiService::class);
        $this->app->alias(JsonApiService::class, HttpServiceInterface::class);
        $this->app->alias(JsonApiService::class, 'json-api.service');
    }

    /**
     * Bind the API factory into the service container.
     */
    protected function bindApiFactory()
    {
        $this->app->singleton(ApiFactoryInterface::class, ApiFactory::class);
        $this->app->resolving(ApiFactoryInterface::class, function (ConfigurableInterface $factory) {
            $factory->configure($this->getConfig('namespaces'));
        });
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
        $this->app->singleton(SchemasRepositoryInterface::class, EloquentSchemasRepository::class);
        $this->app->resolving(SchemasRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure((array) $this->getConfig('schemas', []));
        });
    }

    /**
     * Bind the schema repository into the service container.
     */
    protected function bindSchemaRepository()
    {
        $this->app->singleton(SchemasRepositoryInterface::class, EloquentSchemasRepository::class);
        $this->app->resolving(SchemasRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure((array) $this->getConfig('schemas', []));
        });
    }

    /**
     * Bind the request factory into the service container.
     */
    protected function bindRequestFactory()
    {
        $this->app->singleton(RequestFactoryInterface::class, RequestFactory::class);
        $this->app->singleton(RequestInterpreterInterface::class, RequestInterpreter::class);
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
     * Bind the validator factory into the service container.
     */
    protected function bindValidatorFactory()
    {
        $this->app->singleton(ValidatorFactoryInterface::class, ValidatorFactory::class);
        $this->app->alias(ValidatorFactoryInterface::class, BaseValidatorFactoryInterface::class);
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
        $this->app->singleton(ReplacerInterface::class, Replacer::class);

        $this->app->singleton(ErrorRepositoryInterface::class, function () {
            /** @var ReplacerInterface $replacer */
            $replacer = $this->app->make(ReplacerInterface::class);
            $repository = new ErrorRepository($replacer);
            $repository->configure($this->getErrorConfig());
            return $repository;
        });
        $this->app->alias(ErrorRepositoryInterface::class, 'json-api.errors');
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
     * Bind the store into the service container.
     */
    protected function bindStore()
    {
        $this->app->singleton(StoreInterface::class, Store::class);
        $this->app->alias(StoreInterface::class, 'json-api.store');
    }

    /**
     * Bind the Eloquent adapter into the service container.
     */
    protected function bindEloquentAdapter()
    {
        $this->app->singleton(EloquentAdapter::class, function () {
            $map = (array) $this->getConfig('eloquent-adapter.map');
            $columns = (array) $this->getConfig('eloquent-adapter.columns');

            $models = array_filter(glob('app/Models/*'), 'is_file');
            foreach($models as $model)
            {
                $class = '';
                foreach(explode('/', str_replace('.php', '', $model)) as $item) $class .= ucfirst($item).'\\';
                $class = substr($class, 0, strlen($class) - 1);

                if(class_exists($class))
                {
                    $jsonSchema = (new ReflectionClass($class))->getConstant(static::JSON_API_SCHEMA);
                    if($jsonSchema)
                    {
                        $resourceType = (new $jsonSchema(App::make(SchemaFactoryInterface::class)))
                            ->getResourceType();
                        if(! isset($map[$resourceType]))
                        {
                            $map[$resourceType] = $class;
                        }
                    }
                }
            }

            return new EloquentAdapter($map, $columns);
        });
    }

    /**
     * Bind adapters to the store when it is resolved via the service container.
     */
    protected function bindStoreAdapters()
    {
        $this->app->resolving(StoreInterface::class, function (StoreInterface $store) {
            /** @var EloquentAdapter $eloquent */
            $eloquent = $this->app->make(EloquentAdapter::class);
            $store->register($eloquent);

            foreach ((array) $this->getConfig('adapters') as $adapter) {
                $store->register($this->app->make($adapter));
            }
        });
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
     * Bind pagination into the service container.
     */
    protected function bindPagination()
    {
        $this->app->singleton(PaginatorInterface::class, Paginator::class);
        $this->app->singleton(Page::class);
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
