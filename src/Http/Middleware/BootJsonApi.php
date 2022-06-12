<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use Closure;
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\ResourceNotFoundException;
use CloudCreativity\LaravelJsonApi\Routing\Route;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;

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
    private Container $container;

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
     *
     * - Loads the configuration for the named API that this request is being routed to.
     * - Registers the API in the service container.
     * - Substitutes bindings on the route.
     * - Overrides the Laravel current page resolver so that it uses the JSON API page parameter.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $namespace
     *      the API namespace, as per your JSON API configuration.
     * @return mixed
     */
    public function handle($request, Closure $next, string $namespace)
    {
        /** Build and register the API. */
        $api = $this->bindApi(
            $namespace,
            $request->getSchemeAndHttpHost() . $request->getBaseUrl(),
            $request->route()->parameters
        );

        /** Substitute route bindings. */
        $this->substituteBindings($api);

        /** Set up the Laravel paginator to read from JSON API request instead */
        $this->bindPageResolver();

        return $next($request);
    }

    /**
     * Build the API instance and bind it into the container.
     *
     * @param string $namespace
     * @param string $host
     * @param array $parameters
     * @return Api
     */
    protected function bindApi(string $namespace, string $host, array $parameters = []): Api
    {
        /** @var Repository $repository */
        $repository = $this->container->make(Repository::class);

        $api = $repository->createApi($namespace, $host, $parameters);
        $this->container->instance(Api::class, $api);
        $this->container->alias(Api::class, 'json-api.inbound');

        return $api;
    }

    /**
     * @param Api $api
     * @throws ResourceNotFoundException
     */
    protected function substituteBindings(Api $api): void
    {
        /** @var Route $route */
        $route = $this->container->make(Route::class);
        $route->substituteBindings($api->getStore());
    }

    /**
     * Override the page resolver to read the page parameter from the JSON API request.
     *
     * @return void
     */
    protected function bindPageResolver(): void
    {
        /** Override the current page resolution */
        AbstractPaginator::currentPageResolver(function ($pageName) {
            $pagination = app(QueryParametersInterface::class)->getPaginationParameters() ?: [];

            return $pagination[$pageName] ?? null;
        });
    }

}
