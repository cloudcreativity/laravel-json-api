<?php

namespace CloudCreativity\LaravelJsonApi\Schema;

use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaProviderInterface;
use Neomerx\JsonApi\Schema\Container as BaseContainer;
use Illuminate\Contracts\Container\Container as LaravelContainer;

/**
 * Class Container
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Container extends BaseContainer
{

    /**
     * @var LaravelContainer
     */
    private $container;

    /**
     * Container constructor.
     *
     * @param LaravelContainer $container
     * @param SchemaFactoryInterface $factory
     * @param array $schemas
     */
    public function __construct(LaravelContainer $container, SchemaFactoryInterface $factory, array $schemas = [])
    {
        parent::__construct($factory, $schemas);
        $this->container = $container;
    }

    /**
     * @param string $className
     * @return SchemaProviderInterface
     */
    protected function createSchemaFromClassName($className)
    {
        $schema = $this->container->make($className);

        if (!$schema instanceof SchemaProviderInterface) {
            throw new RuntimeException("Service $className is not a schema.");
        }

        return $schema;
    }
}
