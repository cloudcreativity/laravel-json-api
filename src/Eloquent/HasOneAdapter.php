<?php

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\JsonApi\Contracts\Store\ContainerInterface;
use CloudCreativity\JsonApi\Contracts\Store\RelationshipAdapterInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class HasOneAdapter implements RelationshipAdapterInterface
{

    /**
     * @var string|null
     */
    protected $key;

    /**
     * BelongsToAdapter constructor.
     *
     * @param string|null $key
     */
    public function __construct($key = null)
    {
        $this->key = $key;
    }

    /**
     * @inheritDoc
     */
    public function withAdapters(ContainerInterface $adapters)
    {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function queryRelated($record, $relationshipName, EncodingParametersInterface $parameters)
    {
        $key = $this->key ?: $relationshipName;

        return $record->{$key};
    }

    /**
     * @inheritDoc
     */
    public function queryRelationship($record, $relationshipName, EncodingParametersInterface $parameters)
    {
        return $this->queryRelated($record, $relationshipName, $parameters);
    }

}
