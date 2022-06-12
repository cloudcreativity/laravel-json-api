<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;

/**
 * Class HasMany
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class HasMany extends AbstractManyRelation
{

    /**
     * @param Model $record
     * @param array $relationship
     * @param QueryParametersInterface $parameters
     * @return void
     */
    public function update($record, array $relationship, QueryParametersInterface $parameters)
    {
        $related = $this->findRelated($record, $relationship);
        $relation = $this->getRelation($record, $this->key);

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
     * @param array $relationship
     * @param QueryParametersInterface $parameters
     * @return Model
     */
    public function replace($record, array $relationship, QueryParametersInterface $parameters)
    {
        $this->update($record, $relationship, $parameters);
        $record->refresh(); // in case the relationship has been cached.

        return $record;
    }

    /**
     * Add records to the relationship.
     *
     * Note that the spec says that duplicates MUST NOT be added. The default Laravel
     * behaviour is to add duplicates, therefore we need to do some work to ensure
     * that we only add the records that are not already in the relationship.
     *
     * @param Model $record
     * @param array $relationship
     * @param QueryParametersInterface $parameters
     * @return Model
     */
    public function add($record, array $relationship, QueryParametersInterface $parameters)
    {
        $related = $this->findRelated($record, $relationship);
        $relation = $this->getRelation($record, $this->key);

        $existing = $relation
            ->getQuery()
            ->whereKey($related->modelKeys())
            ->get();

        $relation->saveMany($related->diff($existing));
        $record->refresh(); // in case the relationship has been cached.

        return $record;
    }

    /**
     * @param Model $record
     * @param array $relationship
     * @param QueryParametersInterface $parameters
     * @return Model
     */
    public function remove($record, array $relationship, QueryParametersInterface $parameters)
    {
        $related = $this->findRelated($record, $relationship);
        $relation = $this->getRelation($record, $this->key);

        if ($relation instanceof Relations\BelongsToMany) {
            $relation->detach($related);
        } else {
            $this->detach($relation, $related);
        }

        $record->refresh(); // in case the relationship has been cached

        return $record;
    }

    /**
     * @inheritdoc
     */
    protected function acceptRelation($relation)
    {
        return $relation instanceof Relations\BelongsToMany ||
            $relation instanceof Relations\HasMany ||
            $relation instanceof Relations\MorphMany;
    }

    /**
     * @param Relations\HasMany|Relations\MorphMany $relation
     * @param Collection $existing
     * @param $updated
     */
    protected function sync($relation, Collection $existing, Collection $updated)
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
     * @param Relations\HasMany|Relations\MorphMany $relation
     * @param Collection $remove
     */
    protected function detach($relation, Collection $remove)
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
     * @param array $relationship
     * @return Collection
     */
    protected function findRelated($record, array $relationship)
    {
        $inverse = $this->getRelation($record, $this->key)->getRelated();
        $related = $this->findToMany($relationship);

        $related = collect($related)->filter(function ($model) use ($inverse) {
            return $model instanceof $inverse;
        });

        return new Collection($related);
    }

}
