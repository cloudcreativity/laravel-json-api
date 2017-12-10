<?php

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\JsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\JsonApi\Store\StoreAwareTrait;
use Illuminate\Database\Eloquent\Model;

abstract class AbstractRelation implements RelationshipAdapterInterface, StoreAwareInterface
{

    use StoreAwareTrait;

    /**
     * @var Model
     */
    protected $model;

    /**
     * The model key.
     *
     * @var string
     */
    protected $key;

    /**
     * @var string|null
     */
    protected $relationshipName;

    /**
     * AbstractRelation constructor.
     *
     * @param Model $model
     * @param $key
     * @param null $relationshipName
     */
    public function __construct(Model $model, $key, $relationshipName = null)
    {
        $this->model = $model;
        $this->key = $key;
        $this->relationshipName = $relationshipName;
    }

    /**
     * Set the relationship name.
     *
     * @param $name
     * @return $this
     */
    public function withRelationshipName($name)
    {
        $this->relationshipName = $name;

        return $this;
    }

}
