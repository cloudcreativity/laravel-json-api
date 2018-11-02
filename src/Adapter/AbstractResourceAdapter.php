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
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Store\StoreAwareTrait;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Support\Collection;
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
     * instance. The adapter will then fill the object after it has been
     * created.
     *
     * @param ResourceObject $resource
     * @return mixed
     *      the new domain record.
     */
    abstract protected function createRecord(ResourceObject $resource);

    /**
     * @param $record
     * @param Collection $attributes
     * @return void
     */
    abstract protected function fillAttributes($record, Collection $attributes);

    /**
     * Persist changes to the record.
     *
     * @param $record
     * @return AsynchronousProcess|null
     */
    abstract protected function persist($record);

    /**
     * @inheritdoc
     */
    public function create(array $document, EncodingParametersInterface $parameters)
    {
        $resource = ResourceObject::create($document['data']);
        $record = $this->createRecord($resource);

        return $this->fillAndPersist($record, $resource, $parameters);
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
    public function update($record, array $document, EncodingParametersInterface $parameters)
    {
        $resource = ResourceObject::create($document['data']);

        return $this->fillAndPersist($record, $resource, $parameters) ?: $record;
    }

    /**
     * @inheritDoc
     */
    public function getRelated($field)
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
            $relation->withStore($this->getStore());
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
     * Is the field a fillable relation?
     *
     * @param $field
     * @param $record
     * @return bool
     */
    protected function isFillableRelation($field, $record)
    {
        return $this->isRelation($field) && $this->isFillable($field, $record);
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
     * Fill the domain record with data from the supplied resource object.
     *
     * @param $record
     * @param ResourceObject $resource
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    protected function fill($record, ResourceObject $resource, EncodingParametersInterface $parameters)
    {
        $this->fillAttributes($record, $resource->getAttributes());
        $this->fillRelationships($record, $resource->getRelationships(), $parameters);
    }

    /**
     * Fill relationships from a resource object.
     *
     * @param $record
     * @param Collection $relationships
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    protected function fillRelationships(
        $record,
        Collection $relationships,
        EncodingParametersInterface $parameters
    ) {
        $relationships->filter(function ($value, $field) use ($record) {
            return $this->isFillableRelation($field, $record);
        })->each(function ($value, $field) use ($record, $parameters) {
            $this->fillRelationship($record, $field, $value, $parameters);
        });
    }

    /**
     * Fill a relationship from a resource object.
     *
     * @param $record
     * @param $field
     * @param array $relationship
     * @param EncodingParametersInterface $parameters
     */
    protected function fillRelationship(
        $record,
        $field,
        array $relationship,
        EncodingParametersInterface $parameters
    ) {
        $relation = $this->getRelated($field);

        $relation->update($record, $relationship, $parameters);
    }

    /**
     * Fill any related records that need to be filled after the primary record has been persisted.
     *
     * E.g. this is useful for hydrating many-to-many Eloquent relations, where `$record` must
     * be persisted before the many-to-many database link can be created.
     *
     * @param $record
     * @param ResourceObject $resource
     * @param EncodingParametersInterface $parameters
     */
    protected function fillRelated($record, ResourceObject $resource, EncodingParametersInterface $parameters)
    {
        // no-op
    }

    /**
     * @param mixed $record
     * @param ResourceObject $resource
     * @param EncodingParametersInterface $parameters
     * @return AsynchronousProcess|mixed
     */
    protected function fillAndPersist($record, ResourceObject $resource, EncodingParametersInterface $parameters)
    {
        $this->fill($record, $resource, $parameters);

        if ($async = $this->persist($record)) {
            return $async;
        }

        $this->fillRelated($record, $resource, $parameters);

        return $record;
    }
}
