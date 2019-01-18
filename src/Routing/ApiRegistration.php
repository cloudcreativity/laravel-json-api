<?php

namespace CloudCreativity\LaravelJsonApi\Routing;

use CloudCreativity\LaravelJsonApi\Api\Api;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\Arr;

class ApiRegistration
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
    private $attributes;

    /**
     * Laravel route options.
     *
     * @var array
     */
    private $options;

    /**
     * ApiRegistration constructor.
     *
     * @param Registrar $routes
     * @param Api $api
     * @param array $options
     */
    public function __construct(Registrar $routes, Api $api, array $options = [])
    {
        // this maintains compatibility with passing all through as a single array.
        $keys = ['content-negotiator', 'default-authorizer', 'processes', 'prefix', 'id'];

        $this->routes = $routes;
        $this->api = $api;
        $this->attributes = collect($options)->only($keys)->all();
        $this->options = collect($options)->forget($keys)->all();
    }

    /**
     * @param string $constraint
     * @return $this
     */
    public function defaultId(string $constraint): self
    {
        $this->attributes['id'] = $constraint;

        return $this;
    }

    /**
     * Set the default content negotiator.
     *
     * @param string $negotiator
     * @return $this
     */
    public function defaultContentNegotiator(string $negotiator): self
    {
        $this->attributes['content-negotiator'] = $negotiator;

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
        $this->options['middleware'] = array_merge(
            Arr::wrap($this->options['middleware'] ?? []),
            $middleware
        );

        return $this;
    }

    /**
     * @param string $namespace
     * @return $this
     */
    public function withNamespace(string $namespace): self
    {
        $this->options['namespace'] = $namespace;

        return $this;
    }

    /**
     * @param \Closure $callback
     */
    public function group(\Closure $callback): void
    {
        $this->routes->group($this->options(), function () use ($callback) {
            $group = new ApiGroup($this->routes, $this->api, $this->attributes);
            $callback($group, $this->routes);
            $this->api->providers()->mountAll($group, $this->routes);
        });
    }

    /**
     * @return array
     */
    protected function options(): array
    {
        $url = $this->api->getUrl();

        return collect($this->options)->merge([
            'as' => $url->getName(),
            'prefix' => $url->getNamespace(),
            'middleware' => $this->allMiddleware()
        ])->all();
    }

    /**
     * @return array
     */
    protected function allMiddleware(): array
    {
        return collect(["json-api:{$this->api->getName()}"])
            ->merge(Arr::wrap($this->options['middleware'] ?? []))
            ->all();
    }
}
