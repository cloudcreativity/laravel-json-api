<?php

/**
 * Copyright 2018 Cloud Creativity Limited
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
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Responses\ErrorResponseInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Utils\ErrorReporterInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
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
     * Get an API by name.
     *
     * @param string $apiName
     * @return Api
     * @throws RuntimeException
     *      if the API name is invalid.
     */
    public function api($apiName = 'default')
    {
        /** @var Repository $repo */
        $repo = $this->container->make(Repository::class);

        return $repo->createApi($apiName ?: 'default');
    }

    /**
     * Get the JSON API request, if there is one.
     *
     * @return RequestInterface|null
     */
    public function request()
    {
        if (!$this->container->bound('json-api.request')) {
            return null;
        }

        return $this->container->make('json-api.request');
    }

    /**
     * @return RequestInterface
     */
    public function requestOrFail()
    {
        if (!$request = $this->request()) {
            throw new RuntimeException('No inbound JSON API request.');
        }

        return $request;
    }

    /**
     * Get the API that is handling the inbound HTTP request.
     *
     * @return Api|null
     *      the API, or null if the there is no inbound JSON API HTTP request.
     */
    public function requestApi()
    {
        if (!$this->container->bound('json-api.inbound')) {
            return null;
        }

        return $this->container->make('json-api.inbound');
    }

    /**
     * @return Api
     * @throws RuntimeException
     *      if there is no JSON API handling the inbound request.
     */
    public function requestApiOrFail()
    {
        if (!$api = $this->requestApi()) {
            throw new RuntimeException('No JSON API handling the inbound request.');
        }

        return $api;
    }

    /**
     * Register the routes for an API.
     *
     * @param $apiName
     * @param array $options
     * @param Closure $routes
     * @return void
     */
    public function register($apiName, array $options, Closure $routes)
    {
        /** @var ResourceRegistrar $registrar */
        $registrar = $this->container->make('json-api.registrar');
        $registrar->api($apiName, $options, $routes);
    }

    /**
     * @inheritdoc
     * @todo provide an error reporter on a per-API basis and remove this from the service
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
     * Get the current API, if one has been bound into the container.
     *
     * @return Api
     * @deprecated use `requestApi`
     */
    public function getApi()
    {
        if (!$api = $this->requestApi()) {
            throw new RuntimeException('No active API. The JSON API middleware has not been run.');
        }

        return $api;
    }

    /**
     * @return bool
     * @deprecated use `requestApi()`
     */
    public function hasApi()
    {
        return !is_null($this->requestApi());
    }

    /**
     * Get the current JSON API request, if one has been bound into the container.
     *
     * @return RequestInterface
     * @deprecated use `request()`
     */
    public function getRequest()
    {
        if (!$request = $this->request()) {
            throw new RuntimeException('No JSON API request has been created.');
        }

        return $request;
    }

    /**
     * Has a JSON API request been bound into the container?
     *
     * @return bool
     * @deprecated use `request()`
     */
    public function hasRequest()
    {
        return !is_null($this->request());
    }

}
