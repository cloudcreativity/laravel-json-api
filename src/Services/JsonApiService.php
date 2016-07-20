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
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestHandlerInterface;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Exception;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\AcceptHeader;
use Neomerx\JsonApi\Http\Headers\Header;
use RuntimeException;

/**
 * Class JsonApiService
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiService implements ErrorReporterInterface
{

    /**
     * @var ResourceRegistrar
     */
    private $registrar;

    /**
     * JsonApiService constructor.
     * @param ResourceRegistrar $registrar
     */
    public function __construct(ResourceRegistrar $registrar)
    {
        $this->registrar = $registrar;
    }

    /**
     * Register a resource type with the router.
     *
     * @param string $resourceType
     * @param string $controller
     * @param array $options
     */
    public function resource($resourceType, $controller, array $options = [])
    {
        $this->registrar->resource($resourceType, $controller, $options);
    }

    /**
     * @inheritdoc
     */
    public function report(ErrorResponseInterface $response, Exception $e = null)
    {
        if (!app()->bound(ErrorReporterInterface::class)) {
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
        return app()->bound(ApiInterface::class);
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

        return app(ApiInterface::class);
    }

    /**
     * Get the handler for the current HTTP Request.
     *
     * A request handler will be registered if a request object has been created via the
     * service container (which starts validation).
     *
     * @return RequestHandlerInterface
     */
    public function getRequest()
    {
        if (!app()->bound(RequestHandlerInterface::class)) {
            throw new RuntimeException('No active JSON API request.');
        }

        return app(RequestHandlerInterface::class);
    }

    /**
     * Has a request handler been registered?
     *
     * @return bool
     */
    public function hasRequest()
    {
        return app()->bound(RequestHandlerInterface::class);
    }

    /**
     * Manually boot JSON API support.
     *
     * @param $namespace
     * @param string $accept
     * @param string $contentType
     */
    public function boot(
        $namespace,
        $accept = MediaTypeInterface::JSON_API_MEDIA_TYPE,
        $contentType = MediaTypeInterface::JSON_API_MEDIA_TYPE
    ) {
        $config = (array) config('json-api.namespaces');
        $accept = AcceptHeader::parse($accept);
        $contentType = Header::parse($contentType, Header::HEADER_CONTENT_TYPE);

        if (!array_key_exists($namespace, $config)) {
            throw new RuntimeException("Did not recognise JSON API namespace: $namespace");
        }

        /** @var ApiFactoryInterface $factory */
        $factory = app(ApiFactoryInterface::class);
        $api = $factory->createApi($namespace, $config[$namespace]);
        app()->instance(ApiInterface::class, $api);

        $codecMatcher = $api->getCodecMatcher();
        $codecMatcher->matchEncoder($accept);
        $codecMatcher->matchDecoder($contentType);
    }
}
