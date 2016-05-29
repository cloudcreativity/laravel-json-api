<?php

/**
 * Copyright 2015 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi;

use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\SchemasRepositoryInterface;
use CloudCreativity\JsonApi\Contracts\Stdlib\ConfigurableInterface;
use CloudCreativity\JsonApi\Http\Responses\ResponseFactory;
use CloudCreativity\JsonApi\Http\Responses\Responses;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Neomerx\JsonApi\Factories\Factory;
use Illuminate\Contracts\Routing\ResponseFactory as ResponseFactoryContract;
use Illuminate\Routing\ResponseFactory as IlluminateResponseFactory;

/**
 * Class ServiceProvider
 * @package CloudCreativity\JsonApi\Laravel
 */
class ServiceProvider extends BaseServiceProvider
{

    /**
     * @var bool
     */
    protected $defer = false;

    /**
     * @param Router $router
     * @param Kernel $kernel
     * @param ResponseFactoryContract $responses
     */
    public function boot(
        Router $router,
        Kernel $kernel,
        ResponseFactoryContract $responses
    ) {
        // Allow publishing of config file
        $this->publishes([
            __DIR__ . '/../config/json-api.php' => config_path('json-api.php'),
        ]);

        // Add Json Api middleware to the router.
        $router->middleware('json-api', Http\Middleware\BootJsonApi::class);
        //$router->middleware('json-api-ext', Http\Middleware\SupportedExt::class);

        // If the whole application is set to be a Json Api, push the init middleware into the kernel.
        $global = (bool) $this->getConfig(Config::IS_GLOBAL);

        if (true === $global && method_exists($kernel, 'pushMiddleware')) {
            $kernel->pushMiddleware(Http\Middleware\BootJsonApi::class);
        }

        // Set up a response macro
        if (method_exists($responses, 'macro')) {
            $responses->macro('jsonApi', function () {
                return $this->app->make(ResponseFactory::class);
            });
        }
    }

    /**
     * Register JSON API services.
     *
     * @return void
     */
    public function register()
    {
        $container = $this->app;

        // Factory
        $container->singleton(FactoryInterface::class, Factory::class);
        $container->singleton(SchemaFactoryInterface::class, Factory::class);
        //$container->singleton(ParametersFactoryInterface::class, Factory::class);

        // Codec Matcher Repository
        $container->singleton(CodecMatcherRepositoryInterface::class, Repositories\CodecMatcherRepository::class);
        $container->resolving(CodecMatcherRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure((array) $this->getConfig(Config::CODEC_MATCHER, []));
        });

        // Schemas Repository
        $container->singleton(SchemasRepositoryInterface::class, Repositories\SchemasRepository::class);
        $container->resolving(SchemasRepositoryInterface::class, function (ConfigurableInterface $repository) {
            $repository->configure((array) $this->getConfig(Config::SCHEMAS, []));
        });

        // Responses
        $container->singleton(ResponsesInterface::class, Responses::class);
    }

    /**
     * @param $key
     * @param $default
     * @return array
     */
    protected function getConfig($key, $default = null)
    {
        /** @var Repository $config */
        $config = $this->app->make('config');
        $key = sprintf('%s.%s', Config::NAME, $key);

        return $config->get($key, $default);
    }

}
