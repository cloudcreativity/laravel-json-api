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
     * The resource attribute keys to hydrate.
     *
     * - Empty array = hydrate no attributes.
     * - Non-empty array = hydrate the specified attribute keys (see below).
     * - Null = calculate the attributes to hydrate using `Model::getFillable()`
     *
     * List the keys from the resource's attributes that should be transferred to your
     * model using the `fill()` method. To map a resource attribute key to a different
     * model key, use a key/value pair where the key is the resource attribute and the
     * value is the model attribute.
     *
     * For example:
     *
     * ```
     * $attributes = [
     *  'foo',
     *  'bar' => 'baz'
     *  'foo-bar',
     * ];
     * ```
     *
     * Will transfer the `foo` resource attribute to the model `foo` attribute, and the
     * resource `bar` attribute to the model `baz` attribute. The `foo-bar` resource
     * attribute will be converted to `foo_bar` if the Model uses snake case attributes,
     * or `fooBar` if it does not use snake case.
     *
     * If this property is `null`, the attributes to hydrate will be calculated using
     * `Model::getFillable()`.
     *
     * @var array|null
     */
    protected $attributes = null;

    /**
     * The resource attributes that are dates.
     *
     * If an array, a list of JSON API resource attributes that should be cast to dates.
     * If `null`, the list will be calculated using `Model::getDates()`
     *
     * @var string[]|null
     */
    protected $dates = null;

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
     * @inheritdoc
     */
    public function create(ResourceObjectInterface $resource)
    {
        $record = parent::create($resource);
        $this->hydrateRelated($resource, $record);

        return $record;
    }

    /**
     * @inheritdoc
     */
    public function update(ResourceObjectInterface $resource, $record)
    {
        $record = parent::update($resource, $record);
        $this->hydrateRelated($resource, $record);

        return $record;
    }

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
     * @param Model $record
     */
    protected function persist($record)
    {
        $record->save();
    }

    /**
     * @param StandardObjectInterface $attributes
     *      the attributes received from the client.
     * @param Model $record
     *      the model being hydrated
     * @return array
     *      the JSON API attribute keys to hydrate
     */
    protected function attributeKeys(StandardObjectInterface $attributes, $record)
    {
        if (is_null($this->attributes)) {
            $fillableAttributes = [];
            foreach ($record->getFillable() as $attribute) {
                $fillableAttributes[Str::dasherize($attribute)] = $attribute;
            }
            return $fillableAttributes;
        }
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    protected function hydrateAttributes(StandardObjectInterface $attributes, $record)
    {
        if (!$record instanceof Model) {
            throw new InvalidArgumentException('Expecting an Eloquent model.');
        }

        $data = [];

        foreach ($this->attributeKeys($attributes, $record) as $resourceKey => $modelKey) {
            if (is_numeric($resourceKey)) {
                $resourceKey = $modelKey;
                $modelKey = $this->keyForAttribute($modelKey, $record);
            }

            if ($attributes->has($resourceKey)) {
                $data[$modelKey] = $this->deserializeAttribute($attributes->get($resourceKey), $resourceKey, $record);
            }
        }

        $record->fill($data);
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
     * Convert a resource attribute key into a model attribute key.
     *
     * @param $resourceKey
     * @param Model $model
     * @return string
     */
    protected function keyForAttribute($resourceKey, Model $model)
    {
        return $model::$snakeAttributes ? Str::snake($resourceKey) : Str::camel($resourceKey);
    }

    /**
     * Deserialize a value obtained from the resource's attributes.
     *
     * @param $value
     *      the value that the client provided.
     * @param $resourceKey
     *      the attribute key for the value
     * @param Model $record
     * @return Carbon|null
     */
    protected function deserializeAttribute($value, $resourceKey, $record)
    {
        if ($this->isDateAttribute($resourceKey, $record)) {
            return $this->deserializeDate($value);
        }

        return $value;
    }

    /**
     * @param $value
     * @return Carbon|null
     */
    protected function deserializeDate($value)
    {
        return !is_null($value) ? new Carbon($value) : null;
    }

    /**
     * Is this resource key a date attribute?
     *
     * @param $resourceKey
     * @param Model $record
     * @return bool
     */
    protected function isDateAttribute($resourceKey, $record)
    {
        if (is_null($this->dates)) {
            return in_array(Str::snake($resourceKey), $record->getDates(), true);
        }

        return in_array($resourceKey, $this->dates, true);
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
