<?php

/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Adapter;

use CloudCreativity\LaravelJsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Adapter\ResourceAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\RelationshipsInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Store\StoreAwareTrait;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use CloudCreativity\Utils\Object\StandardObjectInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class AbstractResourceAdaptor
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractResourceAdapter implements ResourceAdapterInterface, StoreAwareInterface
{

    use StoreAwareTrait,
        Concerns\GuardsFields,
        Concerns\FindsManyResources;

    /**
     * Create a new record.
     *
     * Implementing classes need only implement the logic to transfer the minimum
     * amount of data from the resource that is required to construct a new record
     * instance. The adapter will then hydrate the object after it has been
     * created.
     *
     * @param ResourceObjectInterface $resource
     * @return object
     */
    abstract protected function createRecord(ResourceObjectInterface $resource);

    /**
     * @param $record
     * @param StandardObjectInterface $attributes
     * @return void
     * @todo rename this `fillAttributes` to use more Laravel terminology.
     */
    abstract protected function hydrateAttributes($record, StandardObjectInterface $attributes);

    /**
     * Persist changes to the record.
     *
     * @param $record
     * @return object|void
     */
    abstract protected function persist($record);

    /**
     * @inheritdoc
     */
    public function create(ResourceObjectInterface $resource, EncodingParametersInterface $parameters)
    {
        $record = $this->createRecord($resource);
        $this->hydrateAttributes($record, $resource->getAttributes());
        $this->hydrateRelationships($record, $resource->getRelationships(), $parameters);
        $record = $this->persist($record) ?: $record;

        if (method_exists($this, 'hydrateRelated')) {
            $record = $this->hydrateRelated($record, $resource, $parameters) ?: $record;
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function read($resourceId, EncodingParametersInterface $parameters)
    {
        return $this->find($resourceId);
    }

    /**
     * @inheritdoc
     */
    public function update($record, ResourceObjectInterface $resource, EncodingParametersInterface $parameters)
    {
        $this->hydrateAttributes($record, $resource->getAttributes());
        $this->hydrateRelationships($record, $resource->getRelationships(), $parameters);
        $record = $this->persist($record) ?: $record;

        if (method_exists($this, 'hydrateRelated')) {
            $record = $this->hydrateRelated($record, $resource, $parameters) ?: $record;
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function related($field)
    {
        if (!$method = $this->methodForRelation($field)) {
            throw new RuntimeException("No relationship method implemented for field {$field}.");
        }

        $relation = $this->{$method}();

        if (!$relation instanceof RelationshipAdapterInterface) {
            throw new RuntimeException("Method {$method} did not return a relationship adapter.");
        }

        $relation->withFieldName($field);

        if ($relation instanceof StoreAwareInterface) {
            $relation->withStore($this->store());
        }

        return $relation;
    }

    /**
     * @param $field
     * @return bool
     */
    protected function isRelation($field)
    {
        return !empty($this->methodForRelation($field));
    }

    /**
     * @param $field
     * @return string|null
     */
    protected function methodForRelation($field)
    {
        $method = Str::camelize($field);

        return method_exists($this, $method) ? $method : null;
    }

    /**
     * @param $record
     * @param RelationshipsInterface $relationships
     * @param EncodingParametersInterface $parameters
     * @return void
     * @deprecated 2.0.0 use `fillRelationships` directly.
     */
    protected function hydrateRelationships(
        $record,
        RelationshipsInterface $relationships,
        EncodingParametersInterface $parameters
    ) {
        $this->fillRelationships($record, $relationships, $parameters);
    }

    /**
     * Fill relationships from a resource object.
     *
     * @param $record
     * @param RelationshipsInterface $relationships
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    protected function fillRelationships(
        $record,
        RelationshipsInterface $relationships,
        EncodingParametersInterface $parameters
    ) {
        foreach ($relationships->getAll() as $field => $relationship) {
            /** Skip any fields that are not fillable. */
            if ($this->isNotFillable($field, $record)) {
                continue;
            }

            /** Skip any fields that are not relations */
            if (!$this->isRelation($field)) {
                continue;
            }

            $this->fillRelationship(
                $record,
                $field,
                $relationships->getRelationship($field),
                $parameters
            );
        }
    }

    /**
     * Fill a relationship from a resource object.
     *
     * @param $record
     * @param $field
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     */
    protected function fillRelationship(
        $record,
        $field,
        RelationshipInterface $relationship,
        EncodingParametersInterface $parameters
    ) {
        $relation = $this->related($field);

        $relation->update($record, $relationship, $parameters);
    }

}
