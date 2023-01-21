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

namespace CloudCreativity\LaravelJsonApi\Contracts\Store;

use CloudCreativity\LaravelJsonApi\Contracts\Adapter\ResourceAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\ResourceNotFoundException;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;

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
     * @param string $resourceType
     * @return bool
     */
    public function isType(string $resourceType): bool;

    /**
     * Query the store for records using the supplied parameters.
     *
     * @param string $resourceType
     * @param QueryParametersInterface $params
     * @return mixed
     */
    public function queryRecords($resourceType, QueryParametersInterface $params);

    /**
     * Create a domain record using data from the supplied resource object.
     *
     * @param string $resourceType
     * @param array $document
     *      the JSON API document received from the client.
     * @param QueryParametersInterface $params
     * @return object
     *      the created domain record.
     */
    public function createRecord($resourceType, array $document, QueryParametersInterface $params);

    /**
     * Query the store for a single record using the supplied parameters.
     *
     * @param object $record
     *      the domain record being read.
     * @param QueryParametersInterface $params
     * @return object|null
     */
    public function readRecord($record, QueryParametersInterface $params);

    /**
     * Update a domain record with data from the supplied resource object.
     *
     * @param object $record
     *      the domain record to update.
     * @param array $document
     *      the JSON API document received from the client.
     * @param QueryParametersInterface $params
     * @return object
     *      the updated domain record.
     */
    public function updateRecord($record, array $document, QueryParametersInterface $params);

    /**
     * Delete a domain record.
     *
     * @param $record
     * @param QueryParametersInterface $params
     * @return mixed|null
     */
    public function deleteRecord($record, QueryParametersInterface $params);

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
     * @param QueryParametersInterface $params
     *      the encoding parameters to use for the query.
     * @return mixed
     *      the related records
     */
    public function queryRelated($record, $relationshipName, QueryParametersInterface $params);

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
     * @param QueryParametersInterface $params
     *      the encoding parameters to use for the query.
     * @return mixed
     *      the related records
     */
    public function queryRelationship($record, $relationshipName, QueryParametersInterface $params);

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
     * @param QueryParametersInterface $params
     * @return object
     *      the updated domain record.
     */
    public function replaceRelationship(
        $record,
        $relationshipKey,
        array $document,
        QueryParametersInterface $params
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
     * @param QueryParametersInterface $params
     * @return object
     *      the updated domain record.
     * @throws RuntimeException
     *      if the relationship object is a has-one relationship.
     */
    public function addToRelationship(
        $record,
        $relationshipKey,
        array $document,
        QueryParametersInterface $params
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
     * @param QueryParametersInterface $params
     * @return mixed
     *      the updated domain record.
     * @throws RuntimeException
     *      if the relationship object is a has-one relationship.
     */
    public function removeFromRelationship(
        $record,
        $relationshipKey,
        array $document,
        QueryParametersInterface $params
    );

    /**
     * Does the specified resource exist?
     *
     * @param string $type
     * @param string $id
     * @return bool
     */
    public function exists(string $type, string $id): bool;

    /**
     * Find the domain record for the specified resource.
     *
     * @param string $type
     *      the resource type.
     * @param string $id
     *      the resource id.
     * @return mixed|null
     *      the record, or null if it does not exist.
     */
    public function find(string $type, string $id);

    /**
     * Find the domain record that this resource identifier refers to, or fail if it cannot be found.
     *
     * @param string $type
     *      the resource type.
     * @param string $id
     *      the resource id.
     * @return mixed|null
     *      the record, or null if it does not exist.
     * @throws ResourceNotFoundException
     *      if the record does not exist.
     */
    public function findOrFail(string $type, string $id);

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
     * @return iterable
     *      containing the related domain records.
     */
    public function findToMany(array $relationship): iterable;

    /**
     * Find many domain records using the supplied resource identifiers.
     *
     * The returned collection MUST contain only domain records that match the
     * supplied identifiers, and MUST NOT contain duplicate domain records (even if there
     * are duplicate identifiers). If it cannot find any domain records for the supplied
     * identifiers, it must still return a collection - i.e. the returned collection can
     * be of a length shorter than the collection of identifiers.
     *
     * @param array $identifiers
     * @return iterable
     *      the domain records that match the supplied identifiers.
     */
    public function findMany(iterable $identifiers): iterable;

    /**
     * Get the adapter for the supplied JSON API resource type or domain record.
     *
     * @param string|mixed $resourceType
     *      the resource type (string), or the domain record (object).
     * @return ResourceAdapterInterface
     */
    public function adapterFor($resourceType);

}
