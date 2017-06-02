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

namespace CloudCreativity\LaravelJsonApi\Services;

use Closure;
use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Contracts\Http\Responses\ErrorResponseInterface;
use CloudCreativity\JsonApi\Contracts\Utils\ErrorReporterInterface;
use CloudCreativity\JsonApi\Encoder\Encoder;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Exception;
use Illuminate\Contracts\Container\Container;

/**
 * Class JsonApiService
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiService implements ErrorReporterInterface
{

    /**
     * @var Container
     */
    private $container;

    /**
     * JsonApiService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $apiName
     * @param array $options
     * @param Closure $routes
     * @return void
     */
    public function api($apiName, array $options, Closure $routes)
    {
        /** @var ResourceRegistrar $registrar */
        $registrar = $this->container->make(ResourceRegistrar::class);
        $registrar->api($apiName, $options, $routes);
    }

    /**
     * @param $apiName
     * @param string|null $host
     * @param int $options
     * @param int $depth
     * @return Encoder
     */
    public function encoder($apiName, $host = null, $options = 0, $depth = 512)
    {
        /** @var Repository $repository */
        $repository = $this->container->make(Repository::class);

        return $repository->retrieveEncoder($apiName, $host, $options, $depth);
    }

    /**
     * @inheritdoc
     */
    public function report(ErrorResponseInterface $response, Exception $e = null)
    {
        if (!$this->container->bound(ErrorReporterInterface::class)) {
            return;
        }

        /** @var ErrorReporterInterface $reporter */
        $reporter = $this->container->make(ErrorReporterInterface::class);
        $reporter->report($response, $e);
    }

    /**
     * Get a request interpreter instance.
     *
     * @return RequestInterpreterInterface
     */
    public function getRequestInterpreter()
    {
        return $this->container->make(RequestInterpreterInterface::class);
    }

    /**
     * Get the current API, if one has been bound into the container.
     *
     * @return ApiInterface
     */
    public function getApi()
    {
        if (!$this->hasApi()) {
            throw new RuntimeException('No active API. The JSON API middleware has not been run.');
        }

        return $this->container->make(ApiInterface::class);
    }

    /**
     * Has an API been bound into the container?
     *
     * @return bool
     */
    public function hasApi()
    {
        return $this->container->bound(ApiInterface::class);
    }

    /**
     * Get the current JSON API request, if one has been bound into the container.
     *
     * @return RequestInterface
     */
    public function getRequest()
    {
        if (!$this->hasRequest()) {
            throw new RuntimeException('No JSON API request has been created.');
        }

        return $this->container->make(RequestInterface::class);
    }

    /**
     * Has a JSON API request been bound into the container?
     *
     * @return bool
     */
    public function hasRequest()
    {
        return $this->container->bound(RequestInterface::class);
    }

}
