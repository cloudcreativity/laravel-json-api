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

namespace CloudCreativity\LaravelJsonApi\Routing;

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Ramsey\Uuid\Uuid;

/**
 * Class ResourceGroup
 *
 * @package CloudCreativity\LaravelJsonApi
 */
final class ResourceGroup
{

    const METHODS = [
        'index' => 'get',
        'create' => 'post',
        'read' => 'get',
        'update' => 'patch',
        'delete' => 'delete',
    ];

    use RegistersResources;

    /**
     * ResourceGroup constructor.
     *
     * @param string $resourceType
     * @param array $options
     */
    public function __construct(string $resourceType, array $options = [])
    {
        $this->resourceType = $resourceType;
        $this->options = $options;
    }

    /**
     * @param Registrar $router
     * @return void
     */
    public function register(Registrar $router): void
    {
        $router->group($this->attributes(), function (Registrar $router) {
            /** Async process routes */
            if ($this->hasAsync()) {
                $this->registerProcesses($router);
            }

            /** Primary resource routes. */
            $router->group([], function ($router) {
                $this->registerResource($router);
            });

            /** Resource relationship routes */
            $this->registerRelationships($router);
        });
    }

    /**
     * @param Registrar $router
     * @return void
     */
    private function registerResource(Registrar $router): void
    {
        foreach ($this->resourceActions() as $action) {
            $this->routeForResource($router, $action);
        }
    }

    /**
     * @param Registrar $router
     * @return void
     */
    private function registerRelationships(Registrar $router): void
    {
        (new RelationshipsGroup($this->resourceType, $this->options))->register($router);
    }

    /**
     * Add routes for async processes.
     *
     * @param Registrar $router
     */
    private function registerProcesses(Registrar $router): void
    {
        $this->routeForProcess(
            $router,
            'get',
            $this->baseProcessUrl(),
            $this->actionForRoute('processes')
        );

        $this->routeForProcess(
            $router,
            'get',
            $this->processUrl(),
            $this->actionForRoute('process')
        );
    }

    /**
     * @return string
     */
    private function contentNegotiation(): string
    {
        $cn = $this->options['content-negotiator'] ?? null;

        return $cn ? "json-api.content:{$cn}" : 'json-api.content';
    }

    /**
     * @return array
     */
    private function attributes(): array
    {
        return [
            'middleware' => $this->middleware(),
            'as' => "{$this->resourceType}.",
            'prefix' => $this->resourceType,
        ];
    }

    /**
     * @return array
     */
    private function middleware(): array
    {
        return collect($this->contentNegotiation())
            ->merge($this->options['middleware'] ?? [])
            ->all();
    }

    /**
     * @return array
     */
    private function resourceActions(): array
    {
        return $this->diffActions(['index', 'create', 'read', 'update', 'delete'], $this->options);
    }

    /**
     * @return bool
     */
    private function hasAsync(): bool
    {
        return $this->options['async'] ?? false;
    }

    /**
     * @return string
     */
    private function baseProcessUrl(): string
    {
        return '/' . $this->processType();
    }

    /**
     * @return string
     */
    private function processUrl(): string
    {
        return $this->baseProcessUrl() . '/' . $this->processIdParameter();
    }

    /**
     * @return string
     */
    private function processIdParameter(): string
    {
        return '{' . ResourceRegistrar::PARAM_PROCESS_ID . '}';
    }

    /**
     * @return string
     */
    private function processType(): string
    {
        return $this->options['processes'] ?? ResourceRegistrar::KEYWORD_PROCESSES;
    }

    /**
     * @param string $uri
     * @return string|null
     */
    private function idConstraintForProcess(string $uri): ?string
    {
        if (!Str::contains($uri, $this->processIdParameter())) {
            return null;
        }

        return $this->options['async_id'] ?? Uuid::VALID_PATTERN;
    }

    /**
     * @param Registrar $router
     * @param string $method
     * @param string $uri
     * @param array $action
     * @return Route
     */
    private function routeForProcess(Registrar $router, string $method, string $uri, array $action): Route
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
     * @param Registrar $router
     * @param string $action
     * @return Route
     */
    private function routeForResource(Registrar $router, string $action): Route
    {
        return $this->createRoute(
            $router,
            $this->methodForAction($action),
            $this->urlForAction($action),
            $this->actionForRoute($action)
        );
    }

    /**
     * @param string $action
     * @return string
     */
    private function urlForAction(string $action): string
    {
        if (in_array($action, ['index', 'create'], true)) {
            return $this->baseUrl();
        }

        return $this->resourceUrl();
    }

    /**
     * @param string $action
     * @return string
     */
    private function methodForAction(string $action): string
    {
        return self::METHODS[$action];
    }

    /**
     * @param string $action
     * @return array
     */
    private function actionForRoute(string $action): array
    {
        return [
            'uses' => $this->controllerAction($action),
            'as' => $action,
        ];
    }

    /**
     * @param string $action
     * @return string
     */
    private function controllerAction(string $action): string
    {
        return sprintf('%s@%s', $this->controller(), $action);
    }
}
