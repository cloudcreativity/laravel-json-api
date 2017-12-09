<?php

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\JsonApi\Contracts\Store\RelationshipAdapterInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class MorphToManyAdapter implements RelationshipAdapterInterface
{

    /**
     * @var HasManyAdapter[]
     */
    private $adapters;

    /**
     * MorphToManyAdapter constructor.
     *
     * @param HasManyAdapter[] ...$adapters
     */
    public function __construct(HasManyAdapter ...$adapters)
    {
        $this->adapters = $adapters;
    }

    /**
     * @inheritDoc
     */
    public function queryRelated($record, EncodingParametersInterface $parameters)
    {
        $all = collect();

        foreach ($this->adapters as $adapter) {
            $all = $all->merge($adapter->queryRelated($record, $parameters));
        }

        return $all;
    }

    /**
     * @inheritDoc
     */
    public function queryRelationship($record, EncodingParametersInterface $parameters)
    {
        $all = collect();

        foreach ($this->adapters as $adapter) {
            $all = $all->merge($adapter->queryRelationship($record, $parameters));
        }

        return $all;
    }


}
