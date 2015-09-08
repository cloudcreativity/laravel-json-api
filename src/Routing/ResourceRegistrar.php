<?php

namespace CloudCreativity\JsonApi\Routing;

use Illuminate\Routing\Router;

/**
 * Class ResourceRegistrar
 * @package CloudCreativity\JsonApi
 */
class ResourceRegistrar
{

    /**
     * @var Router
     */
    protected $router;

    /**
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param $name
     * @param $controller
     * @return void
     */
    public function resource($name, $controller)
    {
        $rootUrl = sprintf('/%s', $name);
        $objectUrl = sprintf('%s/{id}', $rootUrl);

        /** Index endpoints */
        $this->router->get($rootUrl, $controller . '@index');

        /** Resource Object endpoints */
        $this->router->post($rootUrl, $controller . '@create');
        $this->router->get($objectUrl, $controller . '@read');
        $this->router->patch($objectUrl, $controller . '@update');
        $this->router->delete($objectUrl, $controller . '@delete');

        // @todo relationship endpoints
    }
}
