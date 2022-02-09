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

use Illuminate\Contracts\Routing\Registrar;

/**
 * Class RouteRegistrar
 *
 * @package CloudCreativity\LaravelJsonApi
 * @method \Illuminate\Routing\Route get(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route post(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route put(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route delete(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route patch(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route options(string $uri, \Closure|array|string|null $action = null)
 * @method \Illuminate\Routing\Route any(string $uri, \Closure|array|string|null $action = null)
 * @method RouteRegistration as(string $value)
 * @method RouteRegistration domain(string $value)
 * @method RouteRegistration middleware(array|string|null $middleware)
 * @method RouteRegistration name(string $value)
 * @method RouteRegistration namespace(string $value)
 * @method RouteRegistration prefix(string  $prefix)
 * @method RouteRegistration where(array  $where)
 * @method RouteRegistration controller(string $controller)
 * @method RouteRegistration field(string $field, string $inverse = null)
 */
class RouteRegistrar
{

    /**
     * @var Registrar
     */
    private $router;

    /**
     * @var array
     */
    private $options;

    /**
     * @var array
     */
    private $defaults;

    /**
     * RouteRegistrar constructor.
     *
     * @param Registrar $router
     * @param array $options
     * @param array $defaults
     */
    public function __construct(Registrar $router, array $options = [], array $defaults = [])
    {
        $this->router = $router;
        $this->options = $options;
        $this->defaults = $defaults;
    }

    /**
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->route(), $name], $arguments);
    }

    /**
     * Register a custom route.

     * @return RouteRegistration
     */
    public function route(): RouteRegistration
    {
        $route = new RouteRegistration($this->router, $this, $this->defaults);
        $route->controller($this->options['controller'] ?? '');

        return $route;
    }

    /**
     * Register routes for the supplied resource type
     *
     * @param string $resourceType
     * @param array $options
     * @return ResourceRegistration
     */
    public function resource(string $resourceType, array $options = []): ResourceRegistration
    {
        return new ResourceRegistration(
            $this->router,
            $resourceType,
            array_merge($this->options, $options)
        );
    }

}
