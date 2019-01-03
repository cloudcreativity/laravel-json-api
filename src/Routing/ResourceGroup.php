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

use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Route;
use Illuminate\Support\Fluent;

/**
 * Class ResourceGroup
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceGroup
{

    use RegistersResources;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * ResourceGroup constructor.
     *
     * @param string $resourceType
     * @param ResolverInterface $resolver
     * @param Fluent $options
     */
    public function __construct($resourceType, ResolverInterface $resolver, Fluent $options)
    {
        $this->resourceType = $resourceType;
        $this->resolver = $resolver;
        $this->options = $options;
    }

    /**
     * @param Registrar $router
     */
    public function addResource(Registrar $router)
    {
        $router->group($this->groupAction(), function (Registrar $router) {
            /** Async process routes */
            $this->addProcessRoutes($router);

            /** Primary resource routes. */
            $router->group([], function ($router) {
                $this->addResourceRoutes($router);
            });

            /** Resource relationship routes */
            $this->addRelationshipRoutes($router);
        });
    }

    /**
     * @return array
     */
    protected function groupAction()
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
    protected function middleware()
    {
        return (array) $this->options->get('middleware');
    }

    /**
     * @param Registrar $router
     */
    protected function addResourceRoutes(Registrar $router)
    {
        foreach ($this->resourceActions() as $action) {
            $this->resourceRoute($router, $action);
        }
    }

    /**
     * @return array
     */
    protected function resourceActions()
    {
        return $this->diffActions(['index', 'create', 'read', 'update', 'delete'], $this->options);
    }

    /**
     * @param Registrar $router
     */
    protected function addRelationshipRoutes(Registrar $router)
    {
        $this->relationshipsGroup()->addRelationships($router);
    }

    /**
     * @return RelationshipsGroup
     */
    protected function relationshipsGroup()
    {
        return new RelationshipsGroup($this->resourceType, $this->options);
    }

    /**
     * Add routes for async processes.
     *
     * @param Registrar $router
     */
    protected function addProcessRoutes(Registrar $router): void
    {
        if (true !== $this->options->get('async')) {
            return;
        }

        $this->createProcessRoute(
            $router,
            'get',
            $this->baseProcessUrl(),
            $this->routeAction('processes')
        );

        $this->createProcessRoute(
            $router,
            'get',
            $this->processUrl(),
            $this->routeAction('process')
        );
    }

    /**
     * @param Registrar $router
     * @param $action
     * @return Route
     */
    protected function resourceRoute(Registrar $router, $action)
    {
        return $this->createRoute(
            $router,
            $this->routeMethod($action),
            $this->routeUrl($action),
            $this->routeAction($action)
        );
    }

    /**
     * @param $action
     * @return string
     */
    protected function routeUrl($action)
    {
        if (in_array($action, ['index', 'create'], true)) {
            return $this->baseUrl();
        }

        return $this->resourceUrl();
    }

    /**
     * @param $action
     * @return array
     */
    protected function routeAction($action)
    {
        return [
            'uses' => $this->controllerAction($action),
            'as' => $action,
        ];
    }

    /**
     * @param $action
     * @return string
     */
    protected function routeMethod($action)
    {
        $methods = [
            'index' => 'get',
            'create' => 'post',
            'read' => 'get',
            'update' => 'patch',
            'delete' => 'delete',
        ];

        return $methods[$action];
    }

    /**
     * @param $action
     * @return string
     */
    protected function controllerAction($action)
    {
        return sprintf('%s@%s', $this->controller(), $action);
    }
}
