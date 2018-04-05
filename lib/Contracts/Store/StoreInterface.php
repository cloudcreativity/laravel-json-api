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

namespace CloudCreativity\JsonApi\Contracts\Store;

use CloudCreativity\JsonApi\Contracts\Adapter\ResourceAdapterInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierCollectionInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Exceptions\RecordNotFoundException;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Interface StoreInterface
 *
 * The store is responsible for:
 *
 * - Looking up domain records based on a JSON API resource identifier.
 * - Querying domain records based on JSON API query parameters.
 *
 * So that the store can query multiple different types of domain records, it delegates
 * requests to objects that implement the `AdapterInterface`.
 *
 * @package CloudCreativity\JsonApi
 */
interface StoreInterface
{

    /**
     * Is the supplied resource type valid?
     *
     * @param $resourceType
     * @return bool
     */
    public function isType($resourceType);

    /**
     * Query the store for records using the supplied parameters.
     *
     * @param string $resourceType
     * @param EncodingParametersInterface $params
     * @return mixed
     */
    public function queryRecords($resourceType, EncodingParametersInterface $params);

    /**
     * Create a domain record using data from the supplied resource object.
     *
     * @param string $resourceType
     * @param ResourceObjectInterface $resource
     * @param EncodingParametersInterface $params
     * @return object
     *      the created domain record.
     */
    public function createRecord(
        $resourceType,
        ResourceObjectInterface $resource,
        EncodingParametersInterface $params
    );

    /**
     * Query the store for a single record using the supplied parameters.
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param EncodingParametersInterface $params
     * @return object|null
     */
    public function readRecord($resourceType, $resourceId, EncodingParametersInterface $params);

    /**
     * Update a domain record with data from the supplied resource object.
     *
     * @param ResourceObjectInterface $resource
     * @param object $record
     *      the domain record to update.
     * @param EncodingParametersInterface $params
     * @return object
     *      the updated domain record.
     */
    public function updateRecord($record, ResourceObjectInterface $resource, EncodingParametersInterface $params);

    /**
     * Delete a domain record.
     *
     * @param $record
     * @param EncodingParametersInterface $params
     * @return void
     */
    public function deleteRecord($record, EncodingParametersInterface $params);

    /**
     * Query the store for related records using the supplied parameters.
     *
     * For example, if a client is querying the `comments` relationship of a `posts` resource,
     * the store would be queried as follows:
     *
     * ```
     * $comments = $store->queryRelated($post, 'comments', $encodingParameters);
     * ```
     *
     * @param $record
     *      the domain record on which the relationship exists.
     * @param $relationshipName
     *      the name of the relationship that is being queried.
     * @param EncodingParametersInterface $params
     *      the encoding parameters to use for the query.
     * @return mixed
     *      the related records
     */
    public function queryRelated($record, $relationshipName, EncodingParametersInterface $params);

    /**
     * Query the store for relationship data using the supplied parameters.
     *
     * For example, if a client is querying the `comments` relationship of a `posts` resource,
     * the store would be queried as follows:
     *
     * ```
     * $comments = $store->queryRelationship($post, 'comments', $encodingParameters);
     * ```
     *
     * @param $record
     *      the domain record on which the relationship exists.
     * @param $relationshipName
     *      the name of the relationship that is being queried.
     * @param EncodingParametersInterface $params
     *      the encoding parameters to use for the query.
     * @return mixed
     *      the related records
     */
    public function queryRelationship($record, $relationshipName, EncodingParametersInterface $params);

    /**
     * Update a domain record's relationship with data from the supplied relationship object.
     *
     * For a has-one relationship, this changes the relationship to match the supplied relationship
     * object.
     *
     * For a has-many relationship, this completely replaces every member of the relationship, changing
     * it to match the supplied relationship object.
     *
     * @param object $record
     *      the object to hydrate.
     * @param $relationshipKey
     *      the key of the relationship to hydrate.
     * @param RelationshipInterface $relationship
     *      the relationship object to use for the hydration.
     * @param EncodingParametersInterface $params
     * @return object
     *      the updated domain record.
     */
    public function replaceRelationship(
        $record,
        $relationshipKey,
        RelationshipInterface $relationship,
        EncodingParametersInterface $params
    );

    /**
     * Add data to a domain record's relationship using data from the supplied relationship object.
     *
     * For a has-many relationship, this adds the resource identifiers in the relationship to the domain
     * record's relationship. It is not valid for a has-one relationship.
     *
     * @param object $record
     *      the domain record to update.
     * @param $relationshipKey
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $params
     * @return object
     *      the updated domain record.
     * @throws RuntimeException
     *      if the relationship object is a has-one relationship.
     */
    public function addToRelationship(
        $record,
        $relationshipKey,
        RelationshipInterface $relationship,
        EncodingParametersInterface $params
    );

    /**
     * Remove data from a domain record's relationship using data from the supplied relationship object.
     *
     * For a has-many relationship, this removes the resource identifiers in the relationship from the
     * domain record's relationship. It is not valid for a has-one relationship.
     *
     * @param object $record
     *      the domain record to update.
     * @param $relationshipKey
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $params
     * @return object
     *      the updated domain record.
     * @throws RuntimeException
     *      if the relationship object is a has-one relationship.
     */
    public function removeFromRelationship(
        $record,
        $relationshipKey,
        RelationshipInterface $relationship,
        EncodingParametersInterface $params
    );

    /**
     * Does the domain record this resource identifier refers to exist?
     *
     * @param ResourceIdentifierInterface $identifier
     * @return bool
     */
    public function exists(ResourceIdentifierInterface $identifier);

    /**
     * Find the domain record that this resource identifier refers to.
     *
     * @param ResourceIdentifierInterface $identifier
     * @return object|null
     *      the record, or null if it does not exist.
     */
    public function find(ResourceIdentifierInterface $identifier);

    /**
     * Find the domain record that this resource identifier refers to, or fail if it cannot be found.
     *
     * @param ResourceIdentifierInterface $identifier
     * @return object
     *      the record
     * @throws RecordNotFoundException
     *      if the record does not exist.
     */
    public function findOrFail(ResourceIdentifierInterface $identifier);

    /**
     * @param ResourceIdentifierInterface $identifier
     * @return object
     *      the record
     * @throws RecordNotFoundException
     *      if the record does not exist.
     * @deprecated use `findOrFail`
     */
    public function findRecord(ResourceIdentifierInterface $identifier);

    /**
     * Find many domain records using the supplied resource identifiers.
     *
     * The returned collection MUST contain only domain records that match the
     * supplied identifiers, and MUST NOT contain duplicate domain records (even if there
     * are duplicate identifiers). If it cannot find any domain records for the supplied
     * identifiers, it must still return a collection - i.e. the returned collection can
     * be of a length shorter than the collection of identifiers.
     *
     * @param ResourceIdentifierCollectionInterface $identifiers
     * @return array
     *      an array of domain records that match the supplied identifiers.
     */
    public function findMany(ResourceIdentifierCollectionInterface $identifiers);

    /**
     * Get the adapter for the supplied JSON API resource type or domain record.
     *
     * @param string|object $resourceType
     * @return ResourceAdapterInterface
     */
    public function adapterFor($resourceType);

}
