<?php

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\JsonApi\Contracts\Adapter\HasManyAdapterInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class MorphHasMany implements HasManyAdapterInterface
{

    /**
     * @var HasManyAdapterInterface[]
     */
    private $adapters;

    /**
     * MorphToManyAdapter constructor.
     *
     * @param HasManyAdapterInterface[] ...$adapters
     */
    public function __construct(HasManyAdapterInterface ...$adapters)
    {
        $this->adapters = $adapters;
    }

    /**
     * Set the relationship name.
     *
     * @param $name
     * @return $this
     */
    public function withRelationshipName($name)
    {
        foreach ($this->adapters as $adapter) {
            if (method_exists($adapter, 'withRelationshipName')) {
                $adapter->withRelationshipName($name);
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function query($record, EncodingParametersInterface $parameters)
    {
        $all = collect();

        foreach ($this->adapters as $adapter) {
            $all = $all->merge($adapter->query($record, $parameters));
        }

        return $all;
    }

    /**
     * @inheritDoc
     */
    public function relationship($record, EncodingParametersInterface $parameters)
    {
        $all = collect();

        foreach ($this->adapters as $adapter) {
            $all = $all->merge($adapter->relationship($record, $parameters));
        }

        return $all;
    }

    /**
     * @inheritdoc
     */
    public function update($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->update($record, $relationship, $parameters);
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function replace($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->replace($record, $relationship, $parameters);
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function add($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->add($record, $relationship, $parameters);
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function remove($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->remove($record, $relationship, $parameters);
        }

        return $record;
    }

}
