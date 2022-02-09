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

namespace CloudCreativity\LaravelJsonApi\Services;

use Closure;
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\LaravelJsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiRegistration;
use CloudCreativity\LaravelJsonApi\Routing\JsonApiRegistrar;
use CloudCreativity\LaravelJsonApi\Routing\Route;

/**
 * Class JsonApiService
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiService
{

    /**
     * Get an API by name.
     *
     * @param string|null $apiName
     * @param string|null $host
     * @param array $parameters
     * @return Api
     * @throws RuntimeException
     *      if the API name is invalid.
     */
    public function api($apiName = null, $host = null, array $parameters = [])
    {
        /** @var Repository $repo */
        $repo = app(Repository::class);

        return $repo->createApi(
            $apiName ?: LaravelJsonApi::$defaultApi,
            $host,
            $parameters
        );
    }

    /**
     * Get the current JSON API route.
     *
     * @return Route
     */
    public function currentRoute(): Route
    {
        return app(Route::class);
    }

    /**
     * Get the API that is handling the inbound HTTP request.
     *
     * @return Api|null
     *      the API, or null if the there is no inbound JSON API HTTP request.
     */
    public function requestApi()
    {
        if (!app()->bound('json-api.inbound')) {
            return null;
        }

        return app('json-api.inbound');
    }

    /**
     * Get either the request API or the default API.
     *
     * @param string|null $host
     * @param array $parameters
     * @return Api
     */
    public function requestApiOrDefault($host = null, array $parameters = [])
    {
        return $this->requestApi() ?: $this->api(null, $host, $parameters);
    }

    /**
     * @return Api
     * @throws RuntimeException
     *      if there is no JSON API handling the inbound request.
     */
    public function requestApiOrFail()
    {
        if (!$api = $this->requestApi()) {
            throw new RuntimeException('No JSON API handling the inbound request.');
        }

        return $api;
    }

    /**
     * Register the routes for an API.
     *
     * @param $apiName
     * @param array|Closure $options
     * @param Closure|null $routes
     * @return ApiRegistration
     */
    public function register($apiName, $options = [], Closure $routes = null): ApiRegistration
    {
        /** @var JsonApiRegistrar $registrar */
        $registrar = app('json-api.registrar');

        return $registrar->api($apiName, $options, $routes);
    }

}
