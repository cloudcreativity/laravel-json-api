<?php

/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Contracts\Store;

use CloudCreativity\LaravelJsonApi\Contracts\Adapter\ResourceAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceIdentifierCollectionInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RecordNotFoundException;
use CloudCreativity\LaravelJsonApi\Exceptions\ResourceNotFoundException;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Support\Collection;
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
 * @package CloudCreativity\LaravelJsonApi
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
     * @param array $document
     *      the JSON API document received from the client.
     * @param EncodingParametersInterface $params
     * @return object
     *      the created domain record.
     */
    public function createRecord($resourceType, array $document, EncodingParametersInterface $params);

    /**
     * Query the store for a single record using the supplied parameters.
     *
     * @param object $record
     *      the domain record being read.
     * @param EncodingParametersInterface $params
     * @return object|null
     */
    public function readRecord($record, EncodingParametersInterface $params);

    /**
     * Update a domain record with data from the supplied resource object.
     *
     * @param object $record
     *      the domain record to update.
     * @param array $document
     *      the JSON API document received from the client.
     * @param EncodingParametersInterface $params
     * @return object
     *      the updated domain record.
     */
    public function updateRecord($record, array $document, EncodingParametersInterface $params);

    /**
     * Delete a domain record.
     *
     * @param $record
     * @param EncodingParametersInterface $params
     * @return mixed|null
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
     * @param array $document
     *      the JSON API document received from the client.
     * @param EncodingParametersInterface $params
     * @return object
     *      the updated domain record.
     */
    public function replaceRelationship(
        $record,
        $relationshipKey,
        array $document,
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
     * @param array $document
     *      the JSON API document received from the client.
     * @param EncodingParametersInterface $params
     * @return object
     *      the updated domain record.
     * @throws RuntimeException
     *      if the relationship object is a has-one relationship.
     */
    public function addToRelationship(
        $record,
        $relationshipKey,
        array $document,
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
     * @param array $document
     *      the JSON API document received from the client.
     * @param EncodingParametersInterface $params
     * @return object
     *      the updated domain record.
     * @throws RuntimeException
     *      if the relationship object is a has-one relationship.
     */
    public function removeFromRelationship(
        $record,
        $relationshipKey,
        array $document,
        EncodingParametersInterface $params
    );

    /**
     * Does the specified resource exist?
     *
     * @param ResourceIdentifierInterface|string $type
     * @param string|null $id
     * @return bool
     * @todo in 2.0.0 this will only accept type and id, not a resource identifier object.
     */
    public function exists($type, $id = null);

    /**
     * Find the domain record for the specified resource.
     *
     * @param ResourceIdentifierInterface|array|string $type
     *      the resource identifier, or the string resource type.
     * @param string|null $id
     *      the resource id, required if `$type` is a string.
     * @return object|null
     *      the record, or null if it does not exist.
     * @todo in 2.0.0 this will only accept type and id, not a resource identifier object.
     */
    public function find($type, $id = null);

    /**
     * Find the domain record that this resource identifier refers to, or fail if it cannot be found.
     *
     * @param ResourceIdentifierInterface|array|string $type
     *      the resource identifier, or the string resource type.
     * @param string|null $id
     *      the resource id, required if `$type` is a string.
     * @return object|null
     *      the record, or null if it does not exist.
     * @throws ResourceNotFoundException
     *      if the record does not exist.
     * @throws RecordNotFoundException
     *      if the record does not exist and a resource identifier is provided.
     * @todo in 2.0.0 this will only accept type and id, not a resource identifier object.
     */
    public function findOrFail($type, $id = null);

    /**
     * @param ResourceIdentifierInterface $identifier
     * @return object
     *      the record
     * @throws RecordNotFoundException
     *      if the record does not exist.
     * @deprecated 2.0.0 use `find`
     */
    public function findRecord(ResourceIdentifierInterface $identifier);

    /**
     * Find a related record for a to-one relationship.
     *
     * @param array $relationship
     * @return mixed|null
     *      the domain record or null.
     */
    public function findToOne(array $relationship);

    /**
     * Find related records for a to-many relationship.
     *
     * @param array $relationship
     * @return Collection
     *      containing the related domain records.
     */
    public function findToMany(array $relationship);

    /**
     * Find many domain records using the supplied resource identifiers.
     *
     * The returned collection MUST contain only domain records that match the
     * supplied identifiers, and MUST NOT contain duplicate domain records (even if there
     * are duplicate identifiers). If it cannot find any domain records for the supplied
     * identifiers, it must still return a collection - i.e. the returned collection can
     * be of a length shorter than the collection of identifiers.
     *
     * @param ResourceIdentifierCollectionInterface|array $identifiers
     * @return array
     *      an array of domain records that match the supplied identifiers.
     */
    public function findMany($identifiers);

    /**
     * Get the adapter for the supplied JSON API resource type or domain record.
     *
     * @param string|object $resourceType
     * @return ResourceAdapterInterface
     */
    public function adapterFor($resourceType);

}
