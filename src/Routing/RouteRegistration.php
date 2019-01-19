<?php

namespace CloudCreativity\LaravelJsonApi\Routing;

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
     * @param string $controller
     * @return $this
     */
    public function controller(string $controller): self
    {
        $this->controller = $controller;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function match($methods, $uri, $action = null)
    {
        $route = parent::match($methods, $uri, $action);
        $route->defaults = $this->defaults;

        return $route;
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
        $route = parent::registerRoute($method, $uri, $action);
        $route->defaults = $this->defaults;

        return $route;
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
}
