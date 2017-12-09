<?php

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\JsonApi\Contracts\Store\RelationshipAdapterInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class HasOneAdapter implements RelationshipAdapterInterface
{

    /**
     * @var string
     */
    protected $relationshipName;

    /**
     * @var string
     */
    protected $modelKey;

    /**
     * HasOneAdapter constructor.
     *
     * @param string $relationshipName
     * @param string $modelKey
     */
    public function __construct($relationshipName, $modelKey)
    {
        $this->relationshipName = $relationshipName;
        $this->modelKey = $modelKey;
    }

    /**
     * @inheritDoc
     */
    public function queryRelated($record, EncodingParametersInterface $parameters)
    {
        return $record->{$this->modelKey};
    }

    /**
     * @inheritDoc
     */
    public function queryRelationship($record, EncodingParametersInterface $parameters)
    {
        return $this->queryRelated($record, $parameters);
    }

}
