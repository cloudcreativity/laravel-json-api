<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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
     *
     * - Loads the configuration for the named API that this request is being routed to.
     * - Registers the API in the service container.
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
        $this->bindApi($namespace, $request->getSchemeAndHttpHost() . $request->getBaseUrl());

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
    protected function bindApi(string $namespace, string $host): Api
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
    protected function bindPageResolver(): void
    {
        /** Override the current page resolution */
        AbstractPaginator::currentPageResolver(function ($pageName) {
            $pagination = json_api_request()->getParameters()->getPaginationParameters() ?: [];

            return $pagination[$pageName] ?? null;
        });
    }

}
