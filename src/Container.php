<?php

namespace CloudCreativity\LaravelJsonApi;

use CloudCreativity\JsonApi\AbstractContainer;
use CloudCreativity\JsonApi\Contracts\ResolverInterface;
use Illuminate\Contracts\Container\Container as IlluminateContainer;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;

class Container extends AbstractContainer
{

    /**
     * @var IlluminateContainer
     */
    private $container;

    /**
     * Container constructor.
     *
     * @param IlluminateContainer $container
     * @param ResolverInterface $resolver
     * @param SchemaFactoryInterface $factory
     */
    public function __construct(
        IlluminateContainer $container,
        ResolverInterface $resolver,
        SchemaFactoryInterface $factory
    ) {
        parent::__construct($resolver, $factory);
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    protected function create($className)
    {
        return $this->container->make($className);
    }

}
