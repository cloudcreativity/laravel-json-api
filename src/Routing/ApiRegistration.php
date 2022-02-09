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

use Closure;
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\Arr;
use Illuminate\Support\Str as IlluminateStr;

/**
 * Class ApiRegistration
 *
 * @package CloudCreativity\LaravelJsonApi
 */
final class ApiRegistration
{

    /**
     * @var Registrar
     */
    private $routes;

    /**
     * @var Api
     */
    private $api;

    /**
     * JSON API options.
     *
     * @var array
     */
    private $options;

    /**
     * Laravel route attributes.
     *
     * @var array
     */
    private $attributes;

    /**
     * ApiRegistration constructor.
     *
     * @param Registrar $routes
     * @param Api $api
     * @param array $options
     */
    public function __construct(Registrar $routes, Api $api, array $options = [])
    {
        // this maintains compatibility with passing attributes and options through as a single array.
        $attrs = ['content-negotiator', 'processes', 'prefix', 'id'];

        $this->routes = $routes;
        $this->api = $api;
        $this->options = collect($options)->only($attrs)->all();
        $this->attributes = collect($options)->forget($attrs)->all();
    }

    /**
     * @param string $constraint
     * @return $this
     */
    public function defaultId(string $constraint): self
    {
        $this->options['id'] = $constraint;

        return $this;
    }

    /**
     * @param string $controller
     * @return ApiRegistration
     */
    public function defaultController(string $controller): self
    {
        $this->options['controller'] = $controller;

        return $this;
    }

    /**
     * Use a callback to resolve a controller name for a resource.
     *
     * @param Closure $callback
     * @return $this
     */
    public function controllerResolver(Closure $callback): self
    {
        $this->options['controller_resolver'] = $callback;

        return $this;
    }

    /**
     * Use singular resource names when resolving a controller name.
     *
     * @return ApiRegistration
     */
    public function singularControllers(): self
    {
        return $this->controllerResolver(function (string $resourceType): string {
            $singular = IlluminateStr::singular($resourceType);

            return Str::classify($singular) . 'Controller';
        });
    }

    /**
     * Set the default content negotiator.
     *
     * @param string $negotiator
     * @return $this
     */
    public function defaultContentNegotiator(string $negotiator): self
    {
        $this->options['content-negotiator'] = $negotiator;

        return $this;
    }

    /**
     * Set an authorizer for the entire API.
     *
     * @param string $authorizer
     * @return $this
     */
    public function authorizer(string $authorizer): self
    {
        return $this->middleware("json-api.auth:{$authorizer}");
    }

    /**
     * Add middleware.
     *
     * @param string ...$middleware
     * @return $this
     */
    public function middleware(string ...$middleware): self
    {
        $this->attributes['middleware'] = array_merge(
            Arr::wrap($this->attributes['middleware'] ?? []),
            $middleware
        );

        return $this;
    }

    /**
     * @param string $domain
     * @return ApiRegistration
     */
    public function domain(string $domain): self
    {
        $this->attributes['domain'] = $domain;

        return $this;
    }

    /**
     * @param string $namespace
     * @return $this
     */
    public function withNamespace(string $namespace): self
    {
        $this->attributes['namespace'] = $namespace;

        return $this;
    }

    /**
     * @param Closure $callback
     */
    public function routes(Closure $callback): void
    {
        $this->routes->group($this->attributes(), function () use ($callback) {
            $group = new RouteRegistrar($this->routes, $this->options());
            $callback($group, $this->routes);
            $this->api->providers()->mountAll($group);
        });
    }

    /**
     * @return array
     */
    private function attributes(): array
    {
        $url = $this->api->getUrl();

        return collect($this->attributes)->merge([
            'as' => $url->getName(),
            'prefix' => $url->getNamespace(),
            'middleware' => $this->allMiddleware()
        ])->all();
    }

    /**
     * @return array
     */
    private function options(): array
    {
        return array_merge([
            'processes' => $this->api->getJobs()->getResource(),
        ], $this->options);
    }

    /**
     * @return array
     */
    private function allMiddleware(): array
    {
        return collect(["json-api:{$this->api->getName()}"])
            ->merge(Arr::wrap($this->attributes['middleware'] ?? []))
            ->all();
    }
}
