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
use CloudCreativity\JsonApi\Contracts\Http\HttpServiceInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Contracts\Http\Responses\ErrorResponseInterface;
use CloudCreativity\JsonApi\Contracts\Utils\ErrorReporterInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Exception;
use Illuminate\Contracts\Container\Container;

/**
 * Class JsonApiService
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiService implements HttpServiceInterface, ErrorReporterInterface
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
     * @inheritdoc
     */
    public function getRequestInterpreter()
    {
        return $this->container->make(RequestInterpreterInterface::class);
    }

    /**
     * Has JSON API support been started?
     *
     * @return bool
     * @deprecated use `hasApi()`
     */
    public function isActive()
    {
        return $this->hasApi();
    }

    /**
     * @inheritdoc
     */
    public function getApi()
    {
        if (!$this->hasApi()) {
            throw new RuntimeException('No active API. The JSON API middleware has not been run.');
        }

        return $this->container->make(ApiInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function hasApi()
    {
        return $this->container->bound(ApiInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function getRequest()
    {
        if (!$this->hasRequest()) {
            throw new RuntimeException('No JSON API request has been created.');
        }

        return $this->container->make(RequestInterface::class);
    }

    /**
     * @inheritdoc
     */
    public function hasRequest()
    {
        return $this->container->bound(RequestInterface::class);
    }

}
