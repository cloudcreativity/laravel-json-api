<?php

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\JsonApi\Contracts\Adapter\HasManyAdapterInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class HasMany extends AbstractRelation implements HasManyAdapterInterface
{

    /**
     * @param Model $record
     * @param EncodingParametersInterface $parameters
     * @return mixed
     */
    public function query($record, EncodingParametersInterface $parameters)
    {
        return $record->{$this->key};
    }

    /**
     * @param Model $record
     * @param EncodingParametersInterface $parameters
     * @return mixed
     */
    public function relationship($record, EncodingParametersInterface $parameters)
    {
        return $this->query($record, $parameters);
    }


    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    public function update($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        throw new RuntimeException("Eloquent has-many relations must be replaced not updated.");
    }

    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    public function replace($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $related = $this->store()->findMany($relationship->getIdentifiers());

        $this->getRelation($record)->sync(new Collection($related));
    }

    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    public function add($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $related = $this->store()->findMany($relationship->getIdentifiers());

        $this->getRelation($record)->attach(new Collection($related));
    }

    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    public function remove($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $related = $this->store()->findMany($relationship->getIdentifiers());

        $this->getRelation($record)->detach(new Collection($related));
    }

    /**
     * @param Model $record
     * @return BelongsToMany
     */
    protected function getRelation($record)
    {
        $relation = $record->{$this->key}();

        if (!$relation instanceof BelongsToMany) {
            throw new RuntimeException("Expecting a belongs-to-many relationship.");
        }

        return $relation;
    }

}
