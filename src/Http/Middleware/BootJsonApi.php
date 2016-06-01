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

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use Closure;
use CloudCreativity\JsonApi\Contracts\Http\ApiFactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use CloudCreativity\JsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\JsonApi\Http\Api;
use CloudCreativity\JsonApi\Http\ApiFactory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;

/**
 * Class BootJsonApi
 * @package CloudCreativity\LaravelJsonApi
 */
class BootJsonApi
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Start JSON API support.
     *
     * This middleware:
     * - Loads the configuration for the named API that this request is being routed to.
     * - Registers the API in the service container.
     * - Triggers client/server content negotiation as per the JSON API spec.
     *
     * @param Request $request
     * @param Closure $next
     * @param $namespace
     *      the API namespace, as per your JSON API configuration.
     * @return mixed
     */
    public function handle($request, Closure $next, $namespace)
    {
        $config = (array) config('json-api.namespaces');

        if (!array_key_exists($namespace, $config)) {
            throw new RuntimeException("Did not recognised JSON API namespace: $namespace");
        }

        /** @var ApiFactory $factory */
        $factory = $this->container->make(ApiFactoryInterface::class);
        $api = $factory->createApi($namespace, $this->appendSchemaAndHost($request, (array) $config[$namespace]));
        $this->container->instance(ApiInterface::class, $api);

        /** @var ContentNegotiatorInterface $negotiator */
        $negotiator = $this->container->make(ContentNegotiatorInterface::class);
        /** @var ServerRequestInterface $request */
        $serverRequest = $this->container->make(ServerRequestInterface::class);
        $negotiator->doContentNegotiation($api->getCodecMatcher(), $serverRequest);

        return $next($request);
    }

    /**
     * @param Request $request
     * @param array $config
     * @return string
     */
    private function appendSchemaAndHost(Request $request, array $config)
    {
        if (array_key_exists(ApiFactory::CONFIG_URL_PREFIX, $config)) {
            $config[ApiFactory::CONFIG_URL_PREFIX] =
                $request->getSchemeAndHttpHost() . $config[ApiFactory::CONFIG_URL_PREFIX];
        }

        return $config;
    }

}
