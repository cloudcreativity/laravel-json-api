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
use CloudCreativity\LaravelJsonApi\Contracts\Http\Responses\ErrorResponseInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Utils\ErrorReporterInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Http\Requests\JsonApiRequest;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Exception;
use Illuminate\Contracts\Container\Container;

/**
 * Class JsonApiService
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiService
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var string
     */
    private $default;

    /**
     * JsonApiService constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
        $this->default = 'default';
    }

    /**
     * Set or get the default API name.
     *
     * @param string|null $apiName
     * @return string
     */
    public function defaultApi($apiName = null)
    {
        if (is_null($apiName)) {
            return $this->default;
        }

        if (!is_string($apiName) || empty($apiName)) {
            throw new \InvalidArgumentException('Expecting a non-empty string API name.');
        }

        return $this->default = $apiName;
    }

    /**
     * Get an API by name.
     *
     * @param string|null $apiName
     * @return Api
     * @throws RuntimeException
     *      if the API name is invalid.
     */
    public function api($apiName = null)
    {
        /** @var Repository $repo */
        $repo = $this->container->make(Repository::class);

        return $repo->createApi($apiName ?: $this->default);
    }

    /**
     * Get the JSON API request.
     *
     * @return JsonApiRequest
     */
    public function request()
    {
        return $this->container->make('json-api.request');
    }

    /**
     * Get the inbound JSON API request.
     *
     * @return JsonApiRequest
     * @deprecated 1.0.0 use `request`
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
     * Get either the request API or the default API.
     *
     * @return Api
     */
    public function requestApiOrDefault()
    {
        return $this->requestApi() ?: $this->api();
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
     * @param ErrorResponseInterface $response
     * @param Exception|null $e
     * @return void
     * @deprecated 1.0.0
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

}
