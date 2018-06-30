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

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use Closure;
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Http\Message\ServerRequestInterface;
use function CloudCreativity\LaravelJsonApi\http_contains_body;

/**
 * Class BootJsonApi
 *
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
        /** @var Factory $factory */
        $factory = $this->container->make(Factory::class);
        /** @var ServerRequestInterface $request */
        $serverRequest = $this->container->make(ServerRequestInterface::class);

        /** Build and register the API */
        $api = $this->bindApi($namespace, $request->getSchemeAndHttpHost() . $request->getBaseUrl());

        /** Do content negotiation. */
        $this->doContentNegotiation($factory, $serverRequest, $api->getCodecMatcher());

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

    /**
     * Perform content negotiation.
     *
     * @param HttpFactoryInterface $httpFactory
     * @param ServerRequestInterface $request
     * @param CodecMatcherInterface $codecMatcher
     * @throws JsonApiException
     * @see http://jsonapi.org/format/#content-negotiation
     */
    protected function doContentNegotiation(
        HttpFactoryInterface $httpFactory,
        ServerRequestInterface $request,
        CodecMatcherInterface $codecMatcher
    ) {
        $parser = $httpFactory->createHeaderParametersParser();
        $checker = $httpFactory->createHeadersChecker($codecMatcher);

        $checker->checkHeaders($parser->parse($request, http_contains_body($request)));
    }

}
