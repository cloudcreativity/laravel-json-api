<?php
/**
 * Copyright 2017 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\JsonApi\Contracts\Adapter\HasManyAdapterInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class HasMany
 *
 * @package CloudCreativity\LaravelJsonApi
 * @todo might be best to split has-many-through out into a separate JSON API relation.
 */
class HasMany extends AbstractRelation implements HasManyAdapterInterface
{

    /**
     * @param Model $record
     * @param EncodingParametersInterface $parameters
     * @return mixed
     */
    public function query($record, EncodingParametersInterface $parameters)
    {
        $relation = $this->getRelation($record);
        $adapter = $this->store()->adapterFor($relation->getModel());

        if (!$adapter instanceof AbstractAdapter) {
            throw new RuntimeException('Expecting inverse adapter to be an Eloquent adapter.');
        }

        return $adapter->queryRelation($relation, $parameters);
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
        $related = $this->findRelated($record, $relationship);
        $relation = $this->getWritableRelation($record);

        if ($relation instanceof Relations\BelongsToMany) {
            $relation->sync($related);
            return;
        } else {
            $this->sync($relation, $record->{$this->key}, $related);
        }

        // do not refresh as we expect the resource adapter to refresh the record.
    }

    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    public function replace($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $this->update($record, $relationship, $parameters);
        $record->refresh(); // in case the relationship has been cached.
    }

    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    public function add($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $related = $this->findRelated($record, $relationship);

        $this->getWritableRelation($record)->saveMany($related);
        $record->refresh(); // in case the relationship has been cached.
    }

    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    public function remove($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $related = $this->findRelated($record, $relationship);
        $relation = $this->getWritableRelation($record);

        if ($relation instanceof Relations\BelongsToMany) {
            $relation->detach($related);
        } else {
            $this->detach($relation, $related);
        }

        $record->refresh(); // in case the relationship has been cached
    }

    /**
     * Get the relation for a modification request.
     *
     * @param Model $record
     * @return Relations\BelongsToMany|Relations\HasMany|Relations\MorphMany|Relations\HasManyThrough
     */
    private function getRelation($record)
    {
        $relation = $record->{$this->key}();

        if (!$this->acceptRelation($relation)) {
            throw new RuntimeException(
                "Expecting an Eloquent has-many, has-many-through or belongs-to-many relationship."
            );
        }

        return $relation;
    }

    /**
     * @param $record
     * @return Relations\BelongsToMany|Relations\HasMany|Relations\MorphMany
     */
    private function getWritableRelation($record)
    {
        $relation = $this->getRelation($record);

        if ($relation instanceof Relations\HasManyThrough) {
            throw new RuntimeException('Modifying a has-many-through Eloquent relation is not supported.');
        }

        return $relation;
    }

    /**
     * Is the relation acceptable for this JSON API relationship?
     *
     * @param $relation
     * @return bool
     */
    private function acceptRelation($relation)
    {
        return $relation instanceof Relations\HasManyThrough ||
            $relation instanceof Relations\BelongsToMany ||
            $relation instanceof Relations\HasMany ||
            $relation instanceof Relations\MorphMany;
    }

    /**
     * @param Relations\HasMany $relation
     * @param Collection $existing
     * @param $updated
     */
    private function sync(Relations\HasMany $relation, Collection $existing, Collection $updated)
    {
        $add = collect($updated)->reject(function ($model) use ($existing) {
            return $existing->contains($model);
        });

        $remove = $existing->reject(function ($model) use ($updated) {
            return $updated->contains($model);
        });

        if ($remove->isNotEmpty()) {
            $this->detach($relation, $remove);
        }

        $relation->saveMany($add);
    }

    /**
     * @param Relations\HasMany $relation
     * @param Collection $remove
     */
    private function detach(Relations\HasMany $relation, Collection $remove)
    {
        /** @var Model $model */
        foreach ($remove as $model) {
            $model->setAttribute($relation->getForeignKeyName(), null)->save();
        }
    }

    /**
     * Find the related models for a JSON API relationship object.
     *
     * We look up the models via the store. These then have to be filtered to
     * ensure they are of the correct model type, because this has-many relation
     * might be used in a polymorphic has-many JSON API relation.
     *
     * @todo this is currently inefficient for polymorphic relationships. We
     * need to be able to filter the resource identifiers by the expected resource
     * type before looking them up via the store.
     *
     * @param $record
     * @param RelationshipInterface $relationship
     * @return Collection
     */
    private function findRelated($record, RelationshipInterface $relationship)
    {
        $inverse = $this->getRelation($record)->getRelated();
        $related = $this->store()->findMany($relationship->getIdentifiers());

        $related = collect($related)->filter(function ($model) use ($inverse) {
            return $model instanceof $inverse;
        });

        return new Collection($related);
    }

}
