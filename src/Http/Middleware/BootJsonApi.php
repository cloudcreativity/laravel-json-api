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

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use Closure;
use CloudCreativity\JsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\InboundRequestInterface;
use CloudCreativity\JsonApi\Http\Middleware\ParsesServerRequests;
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class BootJsonApi
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class BootJsonApi
{

    use ParsesServerRequests;

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
        /** @var Factory $factory */
        $factory = $this->container->make(Factory::class);
        /** @var ServerRequestInterface $request */
        $serverRequest = $this->container->make(ServerRequestInterface::class);

        /** Build and register the API */
        $api = $this->bindApi($namespace, $request->getSchemeAndHttpHost());

        /** Build and register the JSON API inbound request */
        $this->bindRequest($factory, $serverRequest, $request, $api);

        /** Set up the Laravel paginator to read from JSON API request instead */
        $this->bindPageResolver();

        return $next($request);
    }

    /**
     * Build the API instance and bind it into the container.
     *
     * @param $namespace
     * @param $host
     * @return Api
     */
    protected function bindApi($namespace, $host)
    {
        /** @var Repository $repository */
        $repository = $this->container->make(Repository::class);

        $api = $repository->createApi($namespace, $host);
        $this->container->instance(Api::class, $api);
        $this->container->alias(Api::class, 'json-api.inbound');

        return $api;
    }

    /**
     * @param FactoryInterface $factory
     * @param ServerRequestInterface $serverRequest
     * @param Request $httpRequest
     * @param Api $api
     * @return void
     */
    protected function bindRequest(
        FactoryInterface $factory,
        ServerRequestInterface $serverRequest,
        Request $httpRequest,
        Api $api
    ) {
        $inboundRequest = $this->parseServerRequest(
            $serverRequest,
            $factory,
            $api->getStore(),
            $api->getCodecMatcher(),
            $httpRequest->route(ResourceRegistrar::PARAM_RESOURCE_TYPE),
            $httpRequest->route(ResourceRegistrar::PARAM_RESOURCE_ID),
            $httpRequest->route(ResourceRegistrar::PARAM_RELATIONSHIP_NAME),
            $httpRequest->is('*/relationships/*')
        );

        $this->container->instance(InboundRequestInterface::class, $inboundRequest);
        $this->container->alias(InboundRequestInterface::class, 'json-api.request');
    }

    /**
     * Override the page resolver to read the page parameter from the JSON API request.
     *
     * @return void
     */
    protected function bindPageResolver()
    {
        /** Override the current page resolution */
        AbstractPaginator::currentPageResolver(function ($pageName) {
            /** @var InboundRequestInterface $request */
            $request = app(InboundRequestInterface::class);
            $pagination = (array) $request->getParameters()->getPaginationParameters();

            return isset($pagination[$pageName]) ? $pagination[$pageName] : null;
        });
    }
}
