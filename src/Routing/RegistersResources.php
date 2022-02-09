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

namespace CloudCreativity\LaravelJsonApi\Routing;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;

/**
 * Class RegistersResources
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait RegistersResources
{

    /**
     * @var Registrar
     */
    private $router;

    /**
     * @var string
     */
    private $resourceType;

    /**
     * @var array
     */
    private $options;

    /**
     * @return string
     */
    private function baseUrl(): string
    {
        return '/';
    }

    /**
     * @return string
     */
    private function resourceUrl(): string
    {
        return $this->baseUrl() . '/' . $this->resourceIdParameter();
    }

    /**
     * @return string
     */
    private function resourceIdParameter(): string
    {
        return '{' . ResourceRegistrar::PARAM_RESOURCE_ID . '}';
    }

    /**
     * @param string $url
     * @return string|null
     */
    private function idConstraint($url): ?string
    {
        if (!Str::contains($url, $this->resourceIdParameter())) {
            return null;
        }

        return $this->options['id'] ?? null;
    }

    /**
     * @return string
     */
    private function controller(): string
    {
        return $this->options['controller'] ?? '\\' . JsonApiController::class;
    }

    /**
     * @param string $method
     * @param string $uri
     * @param array $action
     * @return Route
     */
    private function createRoute(string $method, string $uri, array $action): Route
    {
        /** @var Route $route */
        $route = $this->router->{$method}($uri, $action);
        $route->defaults(ResourceRegistrar::PARAM_RESOURCE_TYPE, $this->resourceType);

        if ($idConstraint = $this->idConstraint($uri)) {
            $route->where(ResourceRegistrar::PARAM_RESOURCE_ID, $idConstraint);
        }

        return $route;
    }

    /**
     * @param array $defaults
     * @param array $options
     * @return array
     */
    private function diffActions(array $defaults, array $options): array
    {
        if ($only = $options['only'] ?? null) {
            return collect($defaults)->intersect($only)->all();
        } elseif ($except = $options['except'] ?? null) {
            return collect($defaults)->diff($except)->all();
        }

        return $defaults;
    }

}
