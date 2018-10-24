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

namespace CloudCreativity\LaravelJsonApi\Routing;

use ArrayAccess;
use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Route;
use Illuminate\Support\Fluent;
use Ramsey\Uuid\Uuid;

/**
 * Class RegistersResources
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait RegistersResources
{

    /**
     * @var string
     */
    protected $resourceType;

    /**
     * @var Fluent
     */
    protected $options;

    /**
     * @return string
     */
    protected function baseUrl()
    {
        return '/';
    }

    /**
     * @return string
     */
    protected function resourceUrl()
    {
        return sprintf('%s/{%s}', $this->baseUrl(), ResourceRegistrar::PARAM_RESOURCE_ID);
    }

    /**
     * @return string
     */
    protected function baseProcessUrl(): string
    {
        return '/' . ResourceRegistrar::KEYWORD_PROCESSES;
    }

    /**
     * @return string
     */
    protected function processUrl(): string
    {
        return sprintf('%s/{%s}', $this->baseProcessUrl(), ResourceRegistrar::PARAM_PROCESS_ID);
    }

    /**
     * @return string
     * @todo allow this to be customised.
     */
    protected function processType(): string
    {
        return 'queue-jobs';
    }

    /**
     * @param string $relationship
     * @return string
     */
    protected function relatedUrl($relationship)
    {
        return sprintf('%s/%s', $this->resourceUrl(), $relationship);
    }

    /**
     * @param $relationship
     * @return string
     */
    protected function relationshipUrl($relationship)
    {
        return sprintf(
            '%s/%s/%s',
            $this->resourceUrl(),
            ResourceRegistrar::KEYWORD_RELATIONSHIPS,
            $relationship
        );
    }

    /**
     * @param string $url
     * @return string|null
     */
    protected function idConstraint($url)
    {
        if ($this->baseUrl() === $url) {
            return null;
        }

        return $this->options->get('id');
    }

    /**
     * @param string $uri
     * @return string|null
     */
    protected function idConstraintForProcess(string $uri): ?string
    {
        if ($this->baseProcessUrl() === $uri) {
            return null;
        }

        if ($constraint = $this->options->get('async_id')) {
            return $constraint;
        }

        return Uuid::VALID_PATTERN;
    }

    /**
     * @return string
     */
    protected function controller()
    {
        if (is_string($controller = $this->options->get('controller'))) {
            return $controller;
        }

        if (true !== $controller) {
            return $this->options['controller'] = '\\' . JsonApiController::class;
        }

        return $this->options['controller'] = Str::classify($this->resourceType) . 'Controller';
    }

    /**
     * @return array
     */
    protected function hasOne()
    {
        return $this->normalizeRelationships('has-one');
    }

    /**
     * @return array
     */
    protected function hasMany()
    {
        return $this->normalizeRelationships('has-many');
    }

    /**
     * @param Registrar $router
     * @param $method
     * @param $uri
     * @param $action
     * @return Route
     */
    protected function createRoute(Registrar $router, $method, $uri, $action)
    {
        /** @var Route $route */
        $route = $router->{$method}($uri, $action);
        $route->defaults(ResourceRegistrar::PARAM_RESOURCE_TYPE, $this->resourceType);

        if ($idConstraint = $this->idConstraint($uri)) {
            $route->where(ResourceRegistrar::PARAM_RESOURCE_ID, $idConstraint);
        }

        return $route;
    }

    /**
     * @param Registrar $router
     * @param $method
     * @param $uri
     * @param $action
     * @return Route
     */
    protected function createProcessRoute(Registrar $router, $method, $uri, $action): Route
    {
        /** @var Route $route */
        $route = $router->{$method}($uri, $action);
        $route->defaults(ResourceRegistrar::PARAM_RESOURCE_TYPE, $this->resourceType);
        $route->defaults(ResourceRegistrar::PARAM_PROCESS_TYPE, $this->processType());

        if ($constraint = $this->idConstraintForProcess($uri)) {
            $route->where(ResourceRegistrar::PARAM_PROCESS_ID, $constraint);
        }

        return $route;
    }

    /**
     * @param array $defaults
     * @param array|ArrayAccess $options
     * @return array
     */
    protected function diffActions(array $defaults, $options)
    {
        if (isset($options['only'])) {
            return array_intersect($defaults, (array) $options['only']);
        } elseif (isset($options['except'])) {
            return array_diff($defaults, (array) $options['except']);
        }

        return $defaults;
    }

    /**
     * @param $optionsKey
     * @return array
     */
    private function normalizeRelationships($optionsKey)
    {
        $relationships = [];

        foreach ((array) $this->options->get($optionsKey) as $key => $value) {
            if (is_numeric($key)) {
                $key = $value;
                $value = [];
            }

            $relationships[$key] = (array) $value;
        }

        return $relationships;
    }
}
