<?php

/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Store;

use CloudCreativity\LaravelJsonApi\Contracts\Adapter\HasManyAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RecordNotFoundException;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifierCollection;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class Store
 *
 * @package CloudCreativity\LaravelJsonApi
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
    public function createRecord($resourceType, array $document, EncodingParametersInterface $params)
    {
        $record = $this
            ->adapterFor($resourceType)
            ->create($document, $params);

        if ($schema = $this->container->getSchemaByResourceType($resourceType)) {
            $identifier = ResourceIdentifier::create($resourceType, $schema->getId($record));
            $this->identityMap->add($identifier, $record);
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function readRecord($record, EncodingParametersInterface $params)
    {
        return $this
            ->adapterFor($record)
            ->read($record, $params);
    }

    /**
     * @inheritDoc
     */
    public function updateRecord($record, array $document, EncodingParametersInterface $params)
    {
        return $this
            ->adapterFor($record)
            ->update($record, $document, $params);
    }

    /**
     * @inheritDoc
     */
    public function deleteRecord($record, EncodingParametersInterface $params)
    {
        $adapter = $this->adapterFor($record);
        $result = $adapter->delete($record, $params);

        if (false === $result) {
            throw new RuntimeException('Record could not be deleted.');
        }

        return true !== $result ? $result : null;
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
            ->getRelated($relationshipName)
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
            ->getRelated($relationshipName)
            ->relationship($record, $params);
    }

    /**
     * @inheritDoc
     */
    public function replaceRelationship(
        $record,
        $relationshipName,
        array $document,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterFor($record)
            ->getRelated($relationshipName)
            ->replace($record, $document, $params);
    }

    /**
     * @inheritDoc
     */
    public function addToRelationship(
        $record,
        $relationshipName,
        array $document,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterForHasMany($record, $relationshipName)
            ->add($record, $document, $params);
    }

    /**
     * @inheritDoc
     */
    public function removeFromRelationship(
        $record,
        $relationshipName,
        array $document,
        EncodingParametersInterface $params
    ) {
        return $this
            ->adapterForHasMany($record, $relationshipName)
            ->remove($record, $document, $params);
    }

    /**
     * @inheritdoc
     */
    public function exists($type, $id = null)
    {
        if ($type instanceof ResourceIdentifierInterface) {
            $identifier = $type;
        } else {
            $identifier = ResourceIdentifier::create($type, $id);
        }

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
    public function find($type, $id = null)
    {
        $identifier = ResourceIdentifier::cast($type, $id);
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
    public function findToOne(array $relationship)
    {
        if (!array_key_exists('data', $relationship)) {
            throw new RuntimeException('Expecting relationship to have a data member.');
        }

        if (is_null($relationship['data'])) {
            return null;
        }

        if (!is_array($relationship['data'])) {
            throw new RuntimeException('Expecting data to be an array with a type and id member.');
        }

        return $this->find($relationship['data']);
    }

    /**
     * @inheritDoc
     */
    public function findToMany(array $relationship)
    {
        $data = $relationship['data'] ?? null;

        if (!is_array($data)) {
            throw new RuntimeException('Expecting relationship to have a data member that is an array.');
        }

        return $this->findMany($data);
    }

    /**
     * @inheritDoc
     */
    public function findMany($identifiers)
    {
        if (is_array($identifiers)) {
            $identifiers = ResourceIdentifierCollection::fromArray($identifiers);
        }

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
        $adapter = $this->adapterFor($resourceType)->getRelated($relationshipName);

        if (!$adapter instanceof HasManyAdapterInterface) {
            throw new RuntimeException("Expecting a has-many relationship adapter.");
        }

        return $adapter;
    }

}
