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

namespace CloudCreativity\LaravelJsonApi\Hydrator;

use Carbon\Carbon;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipsInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Exceptions\InvalidArgumentException;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Hydrator\AbstractHydrator;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use CloudCreativity\Utils\Object\StandardObjectInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class EloquentHydrator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class EloquentHydrator extends AbstractHydrator
{

    /**
     * Resource relationship keys that should be automatically hydrated.
     *
     * This hydrator can hydrate Eloquent `BelongsTo` and `BelongsToMany` relationships. To do so,
     * add the relationship name to this array. As per the attributes above, you can map
     * a resource relationship key to a different model key using a key/value pair. The model key
     * must be the method on the model to get the relationship object.
     *
     * If you want to hydrate a different type of relationship, or want to implement your
     * own logic for a relationship hydration, implement a method called e.g.
     * `hydrateFooRelationship` for hydrating the `foo` resource relationship. If your hydrator
     * has such a method on it, then there is no need to add the relationship key to this property
     * as the method will be invoked anyway.
     *
     * @var string[]
     */
    protected $relationships = [];

    /**
     * @var array|null
     */
    private $normalizedRelationships;

    /**
     * @inheritDoc
     */
    public function updateRelationship($relationshipKey, RelationshipInterface $relationship, $record)
    {
        /** @var Model $record */
        $this->hydrateRelationship($relationshipKey, $relationship, $record);
        $this->persist($record);

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function addToRelationship($relationshipKey, RelationshipInterface $relationship, $record)
    {
        /** @var Model $record */
        $relation = $this->getRelation($relationshipKey, $record);

        if (!$relation instanceof BelongsToMany) {
            throw new RuntimeException("Expecting a belongs-to-many relationship.");
        }

        $related = $this->store()->findMany($relationship->getIdentifiers());
        $relation->attach(new Collection($related));

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function removeFromRelationship($relationshipKey, RelationshipInterface $relationship, $record)
    {
        /** @var Model $record */
        $relation = $this->getRelation($relationshipKey, $record);

        if (!$relation instanceof BelongsToMany) {
            throw new RuntimeException("Expecting a belongs-to-many relationship.");
        }

        $related = $this->store()->findMany($relationship->getIdentifiers());
        $relation->detach(new Collection($related));

        return $record;
    }

    /**
     * @param $relationshipKey
     * @param RelationshipInterface $relationship
     * @param Model $record
     */
    protected function hydrateRelationship($relationshipKey, RelationshipInterface $relationship, $record)
    {
        /** If there's a specific method for the relationship, we'll use that */
        if ($this->callMethodForField($relationshipKey, $relationship, $record)) {
            return;
        }

        /** Try to hydrate a has-one relationship */
        if ($relationship->isHasOne() && $this->hydrateHasOne($relationshipKey, $relationship, $record)) {
            return;
        }

        /** Try to hydrate a has-many relationship */
        if ($relationship->isHasMany() && $this->syncHasMany($relationshipKey, $relationship, $record)) {
            return;
        }

        throw new RuntimeException("Cannot hydrate relationship: $relationshipKey");
    }

    /**
     * @param ResourceObjectInterface $resource
     * @param $record
     * @return array
     */
    protected function hydrateRelated(ResourceObjectInterface $resource, $record)
    {
        $results = (array) $this->hydratingRelated($resource, $record);

        /** @var RelationshipInterface $relationship */
        foreach ($resource->getRelationships()->getAll() as $key => $relationship) {

            /** If there is a specific method for this related member, we'll hydrate that */
            $related = $this->callMethodForField($key, $relationship, $record);

            if (false !== $related) {
                $results = array_merge($results, $related);
                continue;
            }

            /** If this is a has-many, we'll hydrate it. */
            if ($relationship->isHasMany()) {
                $this->syncHasMany($key, $relationship, $record);
            }
        }

        return array_merge($results, (array) $this->hydratedRelated($resource, $record));
    }

    /**
     * @inheritdoc
     */
    protected function hydrateRelationships(RelationshipsInterface $relationships, $record)
    {
        /** @var RelationshipInterface $relationship */
        foreach ($relationships->getAll() as $key => $relationship) {

            /** If there is a specific method for this relationship, we'll hydrate that */
            if ($this->callMethodForField($key, $relationship, $record)) {
                continue;
            }

            /** If this is a has-one, we'll hydrate it. */
            if ($relationship->isHasOne()) {
                $this->hydrateHasOne($key, $relationship, $record);
            }
        }
    }

    /**
     * Hydrate a resource has-one relationship.
     *
     * @param $resourceKey
     * @param RelationshipInterface $relationship
     * @param Model $model
     * @return bool
     *      whether a relationship was hydrated
     */
    protected function hydrateHasOne($resourceKey, RelationshipInterface $relationship, Model $model)
    {
        $relation = $this->getRelation($resourceKey, $model);

        if (!$relation instanceof BelongsTo) {
            return false;
        }

        if ($relationship->hasIdentifier()) {
            $relation->associate($this->store()->find($relationship->getIdentifier()));
        } else {
            $relation->dissociate();
        }

        return true;
    }

    /**
     * Sync a resource has-many relationship.
     *
     * @param $resourceKey
     * @param RelationshipInterface $relationship
     * @param Model $model
     * @return bool
     */
    protected function syncHasMany($resourceKey, RelationshipInterface $relationship, Model $model)
    {
        $relation = $this->getRelation($resourceKey, $model);

        if (!$relation instanceof BelongsToMany) {
            return false;
        }

        $related = $this->store()->findMany($relationship->getIdentifiers());
        $relation->sync(new Collection($related));

        return true;
    }

    /**
     * @param $resourceKey
     * @param Model $model
     * @return Relation|null
     */
    protected function getRelation($resourceKey, Model $model)
    {
        $method = $this->keyForRelationship($resourceKey);

        if (!$method) {
            return null;
        }

        $relation = $model->{$method}();

        return ($relation instanceof Relation) ? $relation : null;
    }

    /**
     * Called before any related hydration occurs.
     *
     * Child classes can overload this method if they need to do any logic pre-hydration.
     *
     * @param ResourceObjectInterface $resource
     * @param $record
     * @return array|null
     */
    protected function hydratingRelated(ResourceObjectInterface $resource, $record)
    {
        return null;
    }

    /**
     * Called after related hydration has occurred.
     *
     * Child classes can overload this method if they need to do any logic post-hydration.
     *
     * @param ResourceObjectInterface $resource
     * @param $record
     * @return array|null
     */
    protected function hydratedRelated(ResourceObjectInterface $resource, $record)
    {
        return null;
    }

    /**
     * Get the model method name for a resource relationship key.
     *
     * @param $resourceKey
     * @return string|null
     *      the method or null if this is not a relationship that should be hydrated.
     */
    private function keyForRelationship($resourceKey)
    {
        $this->normalizeRelationships();

        return isset($this->normalizedRelationships[$resourceKey]) ?
            $this->normalizedRelationships[$resourceKey] : null;
    }

    /**
     * @return void
     */
    private function normalizeRelationships()
    {
        if (is_array($this->normalizedRelationships)) {
            return;
        }

        $this->normalizedRelationships = [];

        foreach ($this->relationships as $resourceKey => $modelKey) {
            if (is_numeric($resourceKey)) {
                $resourceKey = $modelKey;
                $modelKey = Str::camel($modelKey);
            }

            $this->normalizedRelationships[$resourceKey] = $modelKey;
        }
    }
}
