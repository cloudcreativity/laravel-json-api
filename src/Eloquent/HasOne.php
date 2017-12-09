<?php

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class HasOne extends AbstractRelation
{

    /**
     * @inheritDoc
     */
    public function queryRelated($record, EncodingParametersInterface $parameters)
    {
        return $record->{$this->key};
    }

    /**
     * @inheritDoc
     */
    public function queryRelationship($record, EncodingParametersInterface $parameters)
    {
        return $this->queryRelated($record, $parameters);
    }

}
