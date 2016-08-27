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

namespace CloudCreativity\LaravelJsonApi\Services;

use CloudCreativity\JsonApi\Contracts\Http\ApiFactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use CloudCreativity\JsonApi\Contracts\Http\ErrorResponseInterface;
use CloudCreativity\JsonApi\Contracts\Utils\ErrorReporterInterface;
use CloudCreativity\LaravelJsonApi\Http\Requests\JsonApiRequest;
use CloudCreativity\LaravelJsonApi\Http\Requests\ManualRequest;
use CloudCreativity\LaravelJsonApi\Http\Requests\RequestDocument;
use CloudCreativity\LaravelJsonApi\Http\Requests\RequestInitiator;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Exception;
use Illuminate\Contracts\Container\Container;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use RuntimeException;

/**
 * Class JsonApiService
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
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Register a resource type with the router.
     *
     * @param string $resourceType
     * @param string|null $controller
     * @param array $options
     * @return ResourceRegistrar
     */
    public function resource($resourceType, $controller = null, array $options = [])
    {
        /** @var ResourceRegistrar $registrar */
        $registrar = $this->container->make(ResourceRegistrar::class);
        $registrar->resource($resourceType, $controller, $options);

        return $registrar;
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
        $reporter = app(ErrorReporterInterface::class);
        $reporter->report($response, $e);
    }

    /**
     * Has JSON API support been started?
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->container->bound(ApiInterface::class);
    }

    /**
     * Get the active API.
     *
     * An active API will be available once the JSON API middleware has been run.
     *
     * @return ApiInterface
     */
    public function getApi()
    {
        if (!$this->isActive()) {
            throw new RuntimeException('No active API. The JSON API middleware has not been run.');
        }

        return $this->container->make(ApiInterface::class);
    }

    /**
     * @return EncodingParametersInterface
     */
    public function getEncodingParameters()
    {
        if (!$this->hasEncodingParameters()) {
            throw new RuntimeException('No encoding parameters. The JSON API middleware has not been run.');
        }

        return $this->container->make(EncodingParametersInterface::class);
    }

    /**
     * @return bool
     */
    public function hasEncodingParameters()
    {
        return $this->container->bound(EncodingParametersInterface::class);
    }

    /**
     * @return RequestDocument
     */
    public function getRequestDocument()
    {
        if (!$this->hasRequestDocument()) {
            throw new RuntimeException('No JSON API request document.');
        }

        return $this->container->make(RequestDocument::class);
    }

    /**
     * @return bool
     */
    public function hasRequestDocument()
    {
        return $this->container->bound(RequestDocument::class);
    }

    /**
     * Get the current JSON API request.
     *
     * @return JsonApiRequest
     */
    public function getRequest()
    {
        if (!$this->hasRequest()) {
            throw new RuntimeException('No JSON API request has been created.');
        }

        return $this->container->make(JsonApiRequest::class);
    }

    /**
     * Has a request been registered?
     *
     * @return bool
     */
    public function hasRequest()
    {
        return $this->container->bound(JsonApiRequest::class);
    }

    /**
     * Manually boot JSON API support.
     *
     * @param $namespace
     * @param array $parameters
     * @param string $accept
     * @param string $contentType
     */
    public function boot(
        $namespace,
        array $parameters = [],
        $accept = MediaTypeInterface::JSON_API_MEDIA_TYPE,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $config = (array) config('json-api.namespaces');
        $headers = ['Accept' => $accept, 'Content-Type' => $contentType];
        $request = new ManualRequest('GET', $headers, $parameters);

        if (!array_key_exists($namespace, $config)) {
            throw new RuntimeException("Did not recognise JSON API namespace: $namespace");
        }

        /** @var ApiFactoryInterface $factory */
        $factory = $this->container->make(ApiFactoryInterface::class);
        $api = $factory->createApi($namespace, $config[$namespace]);
        $this->container->instance(ApiInterface::class, $api);

        /** @var RequestInitiator $initiator */
        $initiator = $this->container->make(RequestInitiator::class);
        $initiator->doContentNegotiation($api, $request);

        $this->container->instance(EncodingParametersInterface::class, $initiator->parseParameters($request));
    }
}
