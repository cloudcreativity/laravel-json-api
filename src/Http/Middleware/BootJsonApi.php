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
use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Http\Middleware\NegotiatesContent;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
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

    use NegotiatesContent;

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

        /** Do content negotiation, which matches encoders/decoders */
        $this->doContentNegotiation($factory, $serverRequest, $api->getCodecMatcher());

        /** Build and register the JSON API request */
        $this->bindRequest($factory, $serverRequest, $api);

        /** Set up the Laravel paginator to read from JSON API request instead */
        $this->bindPageResolver();

        return $next($request);
    }

    /**
     * Build the API instance and bind it into the container.
     *
     * @param $namespace
     * @param $host
     * @return ApiInterface
     */
    protected function bindApi($namespace, $host)
    {
        /** @var Repository $repository */
        $repository = $this->container->make(Repository::class);

        $api = $repository->retrieveApi($namespace, $host);
        $this->container->instance(ApiInterface::class, $api);

        return $api;
    }

    /**
     * @param FactoryInterface $factory
     * @param ServerRequestInterface $serverRequest
     * @param ApiInterface $api
     * @return RequestInterface
     */
    protected function bindRequest(
        FactoryInterface $factory,
        ServerRequestInterface $serverRequest,
        ApiInterface $api
    ) {
        /** @var RequestInterpreterInterface $interpreter */
        $interpreter = $this->container->make(RequestInterpreterInterface::class);

        $request = $factory->createRequest($serverRequest, $interpreter, $api->getStore());
        $this->container->instance(RequestInterface::class, $request);

        return $request;
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
            /** @var RequestInterface $request */
            $request = app(RequestInterface::class);
            $pagination = (array) $request->getParameters()->getPaginationParameters();

            return isset($pagination[$pageName]) ? $pagination[$pageName] : null;
        });
    }
}
