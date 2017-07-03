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
use CloudCreativity\JsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Contracts\Http\Responses\ErrorResponseInterface;
use CloudCreativity\JsonApi\Contracts\Utils\ErrorReporterInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Http\Responses\Responses;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Exception;
use Illuminate\Contracts\Container\Container;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;

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
     * Get the responses helper.
     *
     * @param EncodingParametersInterface|null $parameters
     * @param SupportedExtensionsInterface|null $extensions
     * @param string|null $apiName
     *      the API name to use if there is no inbound JSON API request.
     * @return Responses
     */
    public function response(
        EncodingParametersInterface $parameters = null,
        SupportedExtensionsInterface $extensions = null,
        $apiName = null
    ) {
        if ($inbound = $this->requestApi()) {
            return $inbound->createResponse($parameters, $extensions);
        }

        return $this->retrieve($apiName)->createResponse($parameters, $extensions);
    }

    /**
     * @param string|null $apiName
     * @return Api
     */
    public function retrieve($apiName = null)
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
        if (!$this->container->bound(RequestInterface::class)) {
            return null;
        }

        return $this->container->make(RequestInterface::class);
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
     * Register the routes for an API.
     *
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
     * @param int $options
     * @param int $depth
     * @return SerializerInterface
     */
    public function encoder($apiName, $options = 0, $depth = 512)
    {
        return $this->retrieve($apiName)->createEncoder($options, $depth);
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
     * Get a request interpreter instance.
     *
     * @return RequestInterpreterInterface
     * @deprecated resolve the request interpreter directly from the container.
     */
    public function getRequestInterpreter()
    {
        return $this->container->make(RequestInterpreterInterface::class);
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
