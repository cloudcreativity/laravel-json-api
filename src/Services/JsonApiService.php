<?php

namespace CloudCreativity\JsonApi\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Routing\ResourceRegistrar;

class JsonApiService
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var ResourceRegistrar
     */
    private $registrar;

    /**
     * JsonApiService constructor.
     * @param Container $container
     * @param ResourceRegistrar $registrar
     */
    public function __construct(Container $container, ResourceRegistrar $registrar)
    {
        $this->container = $container;
        $this->registrar = $registrar;
    }

    /**
     * @param $resourceType
     * @param $controller
     * @param array $options
     */
    public function resource($resourceType, $controller, array $options = [])
    {
        $this->registrar->register($resourceType, $controller, $options);
    }

    /**
     * @return JsonApiContainer
     */
    public function environment()
    {
        return app(JsonApiContainer::class);
    }

    /**
     * @return bool
     */
    public function isJsonApi()
    {
        return $this->container->bound(JsonApiContainer::class);
    }
}
