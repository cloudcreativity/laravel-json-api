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

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Routing\RouteRegistrar as IlluminateRegistrar;
use Illuminate\Support\Str;

/**
 * Class CustomRegistration
 *
 * @package CloudCreativity\LaravelJsonApi
 */
final class RouteRegistration extends IlluminateRegistrar
{

    /**
     * @var RouteRegistrar
     */
    private $registrar;

    /**
     * @var array
     */
    private $defaults;

    /**
     * @var string|null
     */
    private $controller;

    /**
     * CustomRegistration constructor.
     *
     * @param Router $router
     * @param RouteRegistrar $registrar
     * @param array $defaults
     */
    public function __construct(Router $router, RouteRegistrar $registrar, array $defaults = [])
    {
        parent::__construct($router);
        $this->registrar = $registrar;
        $this->defaults = $defaults;
    }

    /**
     * Set the controller for the route.
     *
     * @param string $controller
     * @return $this
     */
    public function controller(string $controller): self
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * Set the route's relationship field name and inverse resource type.
     *
     * @param string $field
     * @param string|null $inverse
     * @return $this
     */
    public function field(string $field, string $inverse = null): self
    {
        $this->defaults = array_merge($this->defaults, [
            ResourceRegistrar::PARAM_RELATIONSHIP_NAME => $field,
            ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE => $inverse ?: Str::plural($field),
        ]);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function match($methods, $uri, $action = null)
    {
        return $this->setupRoute(
            parent::match($methods, $uri, $action)
        );
    }

    /**
     * @inheritdoc
     */
    public function group($callback)
    {
        if ($callback instanceof \Closure) {
            $callback = function () use ($callback) {
                $callback($this->registrar);
            };
        }

        parent::group($callback);
    }

    /**
     * @inheritdoc
     */
    protected function registerRoute($method, $uri, $action = null)
    {
        return $this->setupRoute(
            parent::registerRoute($method, $uri, $action)
        );
    }

    /**
     * @inheritdoc
     */
    protected function compileAction($action)
    {
        $action = parent::compileAction($action);
        $uses = $action['uses'] ?? null;

        if (is_string($uses) && $this->controller && !Str::contains($uses, '@')) {
            $action['uses'] = $this->controller . '@' . $uses;
        }

        return $action;
    }

    /**
     * @param Route $route
     * @return Route
     */
    private function setupRoute(Route $route): Route
    {
        $route->defaults = $this->defaults;

        /** If there is no resource type, we need to prepend content negotiation. */
        if (!isset($this->defaults[ResourceRegistrar::PARAM_RESOURCE_TYPE])) {
            $cn = $this->options['content-negotiator'] ?? '';
            $cn = $cn ? "json-api.content:{$cn}" : 'json-api.content';

            $route->middleware(
                collect($route->middleware())->prepend($cn)->unique()->all()
            );
        }

        return $route;
    }
}
