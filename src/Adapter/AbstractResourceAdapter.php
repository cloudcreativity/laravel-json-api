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

namespace CloudCreativity\LaravelJsonApi\Adapter;

use CloudCreativity\LaravelJsonApi\Codec\ChecksMediaTypes;
use CloudCreativity\LaravelJsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Adapter\ResourceAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Store\StoreAwareTrait;
use CloudCreativity\LaravelJsonApi\Utils\InvokesHooks;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Support\Collection;

/**
 * Class AbstractResourceAdaptor
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractResourceAdapter implements ResourceAdapterInterface, StoreAwareInterface
{

    use StoreAwareTrait,
        InvokesHooks,
        ChecksMediaTypes,
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
     * Delete a record from storage.
     *
     * @param $record
     * @return bool
     *      whether the record was successfully destroyed.
     */
    abstract protected function destroy($record);

    /**
     * @inheritdoc
     */
    public function create(array $document, QueryParametersInterface $parameters)
    {
        $record = $this->createRecord(
            $resource = $this->deserialize($document)
        );

        return $this->fillAndPersist($record, $resource, $parameters, false);
    }

    /**
     * @inheritDoc
     */
    public function read($record, QueryParametersInterface $parameters)
    {
        return $record;
    }

    /**
     * @inheritdoc
     */
    public function update($record, array $document, QueryParametersInterface $parameters)
    {
        $resource = $this->deserialize($document, $record);

        return $this->fillAndPersist($record, $resource, $parameters, true) ?: $record;
    }

    /**
     * @inheritDoc
     */
    public function delete($record, QueryParametersInterface $params)
    {
        if ($result = $this->invoke('deleting', $record)) {
            return $result;
        }

        if (true !== $this->destroy($record)) {
            return false;
        }

        if ($result = $this->invoke('deleted', $record)) {
            return $result;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function getRelated(string $field): RelationshipAdapterInterface
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
     * Deserialize a resource object from a JSON API document.
     *
     * @param array $document
     * @param mixed|null $record
     * @return ResourceObject
     */
    protected function deserialize(array $document, $record = null): ResourceObject
    {
        $data = $document['data'] ?? [];

        if (!is_array($data) || empty($data)) {
            throw new \InvalidArgumentException('Expecting a JSON API document with a data member.');
        }

        return ResourceObject::create($data);
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
     * Get the method name on this adapter for the supplied JSON API field.
     *
     * By default we expect the developer to be following the PSR1 standard,
     * so the method name on the adapter should use camel case.
     *
     * However, some developers may prefer to use the actual JSON API field
     * name. E.g. they could use `user_history` as the JSON API field name
     * and the method name.
     *
     * Therefore we return the field name if it exactly exists on the adapter,
     * otherwise we camelize it.
     *
     * A developer can use completely different logic by overloading this
     * method.
     *
     * @param string $field
     *      the JSON API field name.
     * @return string|null
     *      the adapter's method name, or null if none is implemented.
     */
    protected function methodForRelation($field)
    {
        if (method_exists($this, $field)) {
            return $field;
        }

        $method = Str::camelize($field);

        return method_exists($this, $method) ? $method : null;
    }

    /**
     * Fill the domain record with data from the supplied resource object.
     *
     * @param $record
     * @param ResourceObject $resource
     * @param QueryParametersInterface $parameters
     * @return void
     */
    protected function fill($record, ResourceObject $resource, QueryParametersInterface $parameters)
    {
        $this->fillAttributes($record, $resource->getAttributes());
        $this->fillRelationships($record, $resource->getRelationships(), $parameters);
    }

    /**
     * Fill relationships from a resource object.
     *
     * @param $record
     * @param Collection $relationships
     * @param QueryParametersInterface $parameters
     * @return void
     */
    protected function fillRelationships(
        $record,
        Collection $relationships,
        QueryParametersInterface $parameters
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
     * @param QueryParametersInterface $parameters
     */
    protected function fillRelationship(
        $record,
        $field,
        array $relationship,
        QueryParametersInterface $parameters
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
     * @param QueryParametersInterface $parameters
     */
    protected function fillRelated($record, ResourceObject $resource, QueryParametersInterface $parameters)
    {
        // no-op
    }

    /**
     * @param mixed $record
     * @param ResourceObject $resource
     * @param QueryParametersInterface $parameters
     * @param bool $updating
     * @return AsynchronousProcess|mixed
     */
    protected function fillAndPersist(
        $record,
        ResourceObject $resource,
        QueryParametersInterface $parameters,
        $updating
    ) {
        $this->fill($record, $resource, $parameters);

        if ($result = $this->beforePersist($record, $resource, $updating)) {
            return $result;
        }

        $async = $this->persist($record);

        if ($async instanceof AsynchronousProcess) {
            return $async;
        }

        $this->fillRelated($record, $resource, $parameters);

        if ($result = $this->afterPersist($record, $resource, $updating)) {
            return $result;
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    protected function isInvokedResult($result): bool
    {
        return $result instanceof AsynchronousProcess;
    }

    /**
     * @param $record
     * @param ResourceObject $resource
     * @param $updating
     * @return AsynchronousProcess|null
     */
    private function beforePersist($record, ResourceObject $resource, $updating)
    {
        return $this->invokeMany([
            'saving',
            $updating ? 'updating' : 'creating',
        ], $record, $resource);
    }

    /**
     * @param $record
     * @param ResourceObject $resource
     * @param $updating
     * @return AsynchronousProcess|null
     */
    private function afterPersist($record, ResourceObject $resource, $updating)
    {
        return $this->invokeMany([
            $updating ? 'updated' : 'created',
            'saved',
        ], $record, $resource);
    }

}
