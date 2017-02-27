<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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
use CloudCreativity\JsonApi\Contracts\Http\HttpServiceInterface;
use CloudCreativity\JsonApi\Contracts\Hydrator\HydratesRelatedInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipsInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceInterface;
use CloudCreativity\JsonApi\Contracts\Object\StandardObjectInterface;
use CloudCreativity\JsonApi\Exceptions\InvalidArgumentException;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Hydrator\AbstractHydrator;
use CloudCreativity\JsonApi\Hydrator\RelatedHydratorTrait;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Class EloquentHydrator
 * @package CloudCreativity\LaravelJsonApi
 */
class EloquentHydrator extends AbstractHydrator implements HydratesRelatedInterface
{

    use RelatedHydratorTrait;

    /**
     * The resource attribute keys to hydrate.
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
     * @var array
     */
    protected $attributes = [];

    /**
     * The resource attributes that are dates.
     *
     * @var string[]
     */
    protected $dates = [];

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
     * @var HttpServiceInterface
     */
    protected $service;

    /**
     * @var array|null
     */
    private $normalizedRelationships;

    /**
     * EloquentHydrator constructor.
     * @param HttpServiceInterface $service
     */
    public function __construct(HttpServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * @param StandardObjectInterface $attributes
     * @param $record
     * @return void
     */
    protected function defaultHydrateAttributes(StandardObjectInterface $attributes, $record)
    {
        $hydratorAttributes = $this->attributes;
        if(empty($hydratorAttributes))
        {
            $hydratorAttributes = [];
            foreach($record->getFillable() as $attribute)
            {
                if(strpos($attribute, '_') !== false)
                {
                    $hydratorAttributes[str_replace('_', '-', $attribute)] = $attribute;
                }
                else
                {
                    $hydratorAttributes[] = $attribute;
                }
            }
        }
        return $hydratorAttributes;
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

        foreach ($this->defaultHydrateAttributes($attributes, $record) as $resourceKey => $modelKey) {
            if (is_numeric($resourceKey)) {
                $resourceKey = $modelKey;
                $modelKey = $this->keyForAttribute($modelKey, $record);
            }

            if ($attributes->has($resourceKey)) {
                $data[$modelKey] = $this->deserializeAttribute($attributes->get($resourceKey), $resourceKey);
            }
        }

        $record->fill($data);
    }

    /**
     * @inheritdoc
     */
    public function hydrateRelationship($relationshipKey, RelationshipInterface $relationship, $record)
    {
        /** If there's a specific method for the relationship, we'll use that */
        if ($this->callHydrateRelationship($relationshipKey, $relationship, $record)) {
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
     * @inheritDoc
     */
    public function hydrateRelated(ResourceInterface $resource, $record)
    {
        $results = (array) $this->hydratingRelated($resource, $record);

        /** @var RelationshipInterface $relationship */
        foreach ($resource->getRelationships()->getAll() as $key => $relationship) {

            /** If there is a specific method for this related member, we'll hydrate that */
            $related = $this->callHydrateRelated($key, $relationship, $record);

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
            if ($this->callHydrateRelationship($key, $relationship, $record)) {
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
     * @return Carbon|null
     */
    protected function deserializeAttribute($value, $resourceKey)
    {
        if ($this->isDateAttribute($resourceKey)) {
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
     * @return bool
     */
    protected function isDateAttribute($resourceKey)
    {
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
            $relation->associate($this->findRelated($relationship->getIdentifier()));
        } else {
            $relation->dissociate();
        }

        return true;
    }

    /**
     * Sync a resource has-many relationship
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

        $relation->sync($relationship->getIdentifiers()->getIds());

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
     * @param ResourceIdentifierInterface $identifier
     * @return Model
     */
    protected function findRelated(ResourceIdentifierInterface $identifier)
    {
        $model = $this
            ->service
            ->getApi()
            ->getStore()
            ->find($identifier);

        if (!$model instanceof Model) {
            throw new RuntimeException('Expecting a related resource to be an Eloquent model');
        }

        return $model;
    }

    /**
     * Called before any related hydration occurs.
     *
     * Child classes can overload this method if they need to do any logic pre-hydration.
     *
     * @param ResourceInterface $resource
     * @param $record
     * @return array|null
     */
    protected function hydratingRelated(ResourceInterface $resource, $record)
    {
        return null;
    }

    /**
     * Called after related hydration has occurred.
     *
     * Child classes can overload this method if they need to do any logic post-hydration.
     *
     * @param ResourceInterface $resource
     * @param $record
     * @return array|null
     */
    protected function hydratedRelated(ResourceInterface $resource, $record)
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
