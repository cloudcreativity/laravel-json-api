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

namespace CloudCreativity\JsonApi\Store;

use CloudCreativity\JsonApi\Contracts\Adapter\HasManyAdapterInterface;
use CloudCreativity\JsonApi\Contracts\ContainerInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierCollectionInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Exceptions\RecordNotFoundException;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Object\ResourceIdentifier;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class Store
 *
 * @package CloudCreativity\JsonApi
 */
class Store implements StoreInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var IdentityMap
     */
    private $identityMap;

    /**
     * Store constructor.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->identityMap = new IdentityMap();
    }

    /**
     * @inheritdoc
     */
    public function isType($resourceType)
    {
        return !!$this->container->getAdapterByResourceType($resourceType);
    }

    /**
     * @inheritDoc
     */
    public function queryRecords($resourceType, EncodingParametersInterface $params)
    {
        return $this
            ->adapterFor($resourceType)
            ->query($params);
    }

    /**
     * @inheritDoc
     */
    public function createRecord($resourceType, ResourceObjectInterface $resource, EncodingParametersInterface $params)
    {
        $record = $this
            ->adapterFor($resourceType)
            ->create($resource, $params);

        if ($schema = $this->container->getSchemaByResourceType($resourceType)) {
            $identifier = ResourceIdentifier::create($resourceType, $schema->getId($record));
            $this->identityMap->add($identifier, $record);
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function readRecord($resourceType, $resourceId, EncodingParametersInterface $params)
    {
        $record = $this
            ->adapterFor($resourceType)
            ->read($resourceId, $params);

        $this->identityMap->add(ResourceIdentifier::create($resourceType, $resourceId), $record ?: false);

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function updateRecord($record, ResourceObjectInterface $resource, EncodingParametersInterface $params)
    {
        return $this
            ->adapterFor($record)
            ->update($record, $resource, $params);
    }

    /**
     * @inheritDoc
     */
    public function deleteRecord($record, EncodingParametersInterface $params)
    {
        $adapter = $this->adapterFor($record);

        if (!$adapter->delete($record, $params)) {
            throw new RuntimeException('Record could not be deleted.');
        }
    }

    /**
     * @inheritDoc
     */
    public function queryRelated(
        $record,
        $relationshipName,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterFor($record)
            ->related($relationshipName)
            ->query($record, $params);
    }

    /**
     * @inheritDoc
     */
    public function queryRelationship(
        $record,
        $relationshipName,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterFor($record)
            ->related($relationshipName)
            ->relationship($record, $params);
    }

    /**
     * @inheritDoc
     */
    public function replaceRelationship(
        $record,
        $relationshipName,
        RelationshipInterface $relationship,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterFor($record)
            ->related($relationshipName)
            ->replace($record, $relationship, $params);
    }

    /**
     * @inheritDoc
     */
    public function addToRelationship(
        $record,
        $relationshipName,
        RelationshipInterface $relationship,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterForHasMany($record, $relationshipName)
            ->add($record, $relationship, $params);
    }

    /**
     * @inheritDoc
     */
    public function removeFromRelationship(
        $record,
        $relationshipName,
        RelationshipInterface $relationship,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterForHasMany($record, $relationshipName)
            ->remove($record, $relationship, $params);
    }

    /**
     * @inheritdoc
     */
    public function exists(ResourceIdentifierInterface $identifier)
    {
        $check = $this->identityMap->exists($identifier);

        if (is_bool($check)) {
            return $check;
        }

        $exists = $this
            ->adapterFor($identifier->getType())
            ->exists($identifier->getId());

        $this->identityMap->add($identifier, $exists);

        return $exists;
    }

    /**
     * @inheritdoc
     */
    public function find(ResourceIdentifierInterface $identifier)
    {
        $record = $this->identityMap->find($identifier);

        if (is_object($record)) {
            return $record;
        } elseif (false === $record) {
            return null;
        }

        $record = $this
            ->adapterFor($identifier->getType())
            ->find($identifier->getId());

        $this->identityMap->add($identifier, is_object($record) ? $record : false);

        return $record;
    }

    /**
     * @inheritdoc
     */
    public function findOrFail(ResourceIdentifierInterface $identifier)
    {
        if (!$record = $this->find($identifier)) {
            throw new RecordNotFoundException($identifier);
        }

        return $record;
    }

    /**
     * @inheritDoc
     * @deprecated
     */
    public function findRecord(ResourceIdentifierInterface $identifier)
    {
        return $this->findOrFail($identifier);
    }

    /**
     * @inheritDoc
     */
    public function findMany(ResourceIdentifierCollectionInterface $identifiers)
    {
        $results = [];

        foreach ($identifiers->map() as $resourceType => $ids) {
            $results = array_merge($results, $this->adapterFor($resourceType)->findMany($ids));
        }

        return $results;
    }

    /**
     * @inheritdoc
     */
    public function adapterFor($resourceType)
    {
        if (is_object($resourceType)) {
            return $this->container->getAdapter($resourceType);
        }

        if (!$adapter = $this->container->getAdapterByResourceType($resourceType)) {
            throw new RuntimeException("No adapter for resource type: $resourceType");
        }

        if ($adapter instanceof StoreAwareInterface) {
            $adapter->withStore($this);
        }

        return $adapter;
    }

    /**
     * @param $resourceType
     * @param $relationshipName
     * @return HasManyAdapterInterface
     */
    private function adapterForHasMany($resourceType, $relationshipName)
    {
        $adapter = $this->adapterFor($resourceType)->related($relationshipName);

        if (!$adapter instanceof HasManyAdapterInterface) {
            throw new RuntimeException("Expecting a has-many relationship adapter.");
        }

        return $adapter;
    }

}
