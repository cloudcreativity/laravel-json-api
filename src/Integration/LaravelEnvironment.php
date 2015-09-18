<?php

namespace CloudCreativity\JsonApi\Integration;

use CloudCreativity\JsonApi\Integration\EnvironmentService as BaseService;
use CloudCreativity\JsonApi\Routing\ResourceRegistrar;
use Neomerx\JsonApi\Contracts\Integration\CurrentRequestInterface;
use Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;
use Neomerx\JsonApi\Contracts\Parameters\ParametersFactoryInterface;

class LaravelEnvironment extends BaseService
{

    /**
     * @var ResourceRegistrar
     */
    private $resourceRegistrar;

    /**
     * @param ParametersFactoryInterface $factory
     * @param CurrentRequestInterface $currentRequest
     * @param ExceptionThrowerInterface $exceptionThrower
     * @param ResourceRegistrar $resourceRegistrar
     */
    public function __construct(
        ParametersFactoryInterface $factory,
        CurrentRequestInterface $currentRequest,
        ExceptionThrowerInterface $exceptionThrower,
        ResourceRegistrar $resourceRegistrar
    ) {
        parent::__construct($factory, $currentRequest, $exceptionThrower);
        $this->resourceRegistrar = $resourceRegistrar;
    }

    /**
     * @param $name
     * @param $controller
     * @param array $options
     */
    public function resource($name, $controller, array $options = [])
    {
        $this->resourceRegistrar->resource($name, $controller, $options);
    }
}
