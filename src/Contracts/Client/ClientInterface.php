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

namespace CloudCreativity\LaravelJsonApi\Contracts\Client;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\ClientException;
use Psr\Http\Message\ResponseInterface;

/**
 * Interface ClientInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface ClientInterface
{

    /**
     * Return an instance with the encoding include paths applied.
     *
     * These paths are used for including related resources when
     * encoding a resource to send outbound. This only applies to create
     * and update requests.
     *
     * Note that the JSON API specification says that:
     *
     * > If a relationship is provided in the relationships member of the resource object,
     * > its value MUST be a relationship object with a data member. The value of this key
     * > represents the linkage the new resource is to have.
     *
     * This applies when both creating and updating a resource. This means that
     * you MUST specify the include paths of all relationships that will be
     * serialized and sent outbound, if those relationships are only serialized
     * with a data member if they are included resources.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the client, and MUST return an instance that has the
     * new include paths.
     *
     * @param string ...$includePaths
     * @return ClientInterface
     */
    public function withIncludePaths(...$includePaths);

    /**
     * Return an instance with the encoding field sets applied for the resource type.
     *
     * The field sets are used as the sparse field sets when encoding
     * a resource to send outbound. This only applied to create and update
     * requests.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the client, and MUST return an instance that has the
     * new field sets.
     *
     * @param string $resourceType
     * @param string|string[] $fields
     * @return ClientInterface
     */
    public function withFields($resourceType, $fields);

    /**
     * Return an instance that will keep links in encoded documents.
     *
     * By default a client MUST remove any `links` members from the JSON
     * API document it is sending. This behaviour can be overridden using
     * this method.
     *
     * Note that a client MUST always remove relationships that do not
     * contain the `data` member, because these are not allowed by the
     * spec when sending to a server for a create or update action.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the client, and MUST return an instance that
     * will send links in encoded documents.
     *
     * @return ClientInterface
     */
    public function withLinks();

    /**
     * Return an instance that will send compound documents.
     *
     * By default clients do not send compound documents (JSON API documents
     * with any related resources encoded in the top-level `included` member).
     * This behaviour can be changed by calling this method.
     *
     * This method MUST be implemented in such a way as to retain the
     * immutability of the client, and MUST return an instance that
     * will send compound documents.
     *
     * @return ClientInterface
     */
    public function withCompoundDocuments();

    /**
     * Return an instance that will use the supplied options when making requests.
     *
     * This method MUST be implemented in such a way as to retain the immutability
     * of the client, and MUST return an instance that will use the supplied options.
     *
     * Implementations MAY merge or overwrite any existing options when this method
     * is invoked.
     *
     * @param array $options
     * @return ClientInterface
     */
    public function withOptions(array $options);

    /**
     * Query a resource type on the remote JSON API.
     *
     * @param string $resourceType
     * @param array|QueryParametersInterface $parameters
     *      the parameters to send to the remote server.
     * @return ResponseInterface
     * @throws ClientException
     */
    public function query($resourceType, $parameters = []);

    /**
     * Create a resource on the remote JSON API.
     *
     * @param string $resourceType
     * @param array $payload
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function create($resourceType, array $payload, $parameters = []);

    /**
     * Serialize the domain record and create it on the remote JSON API.
     *
     * @param object $record
     *      the resource fields to send, if sending sparse field-sets.
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function createRecord($record, $parameters = []);

    /**
     * Read the specified resource from the remote JSON API.
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function read($resourceType, $resourceId, $parameters = []);

    /**
     * Read the domain record from the remote JSON API.
     *
     * @param $record
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function readRecord($record, $parameters = []);

    /**
     * Update the specified resource on the remote JSON API.
     *
     * @param $resourceType
     * @param $resourceId
     * @param array $payload
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function update($resourceType, $resourceId, array $payload, $parameters = []);

    /**
     * Serialize the domain record and update it on the remote JSON API.
     *
     * @param object $record
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function updateRecord($record, $parameters = []);

    /**
     * Delete the specified resource from the remote JSON API.
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function delete($resourceType, $resourceId, $parameters = []);

    /**
     * Delete the domain record from the remote JSON API.
     *
     * @param object $record
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function deleteRecord($record, $parameters = []);

    /**
     * Read the related resource for the specified relationship.
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param string $relationship
     *      the field name for the relationship.
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function readRelated($resourceType, $resourceId, $relationship, $parameters = []);

    /**
     * Read the related resource for the provided record's relationship.
     *
     * @param object $record
     * @param string $relationship
     *      the field name for the relationship.
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function readRecordRelated($record, $relationship, $parameters = []);

    /**
     * Read the specified relationship.
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param string $relationship
     *      the field name for the relationship.
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function readRelationship($resourceType, $resourceId, $relationship, $parameters = []);

    /**
     * Read the specified relationship for the provided record.
     *
     * @param object $record
     * @param string $relationship
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function readRecordRelationship($record, $relationship, $parameters = []);

    /**
     * Replace the specified relationship.
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param string $relationship
     *      the field name for the relationship.
     * @param array $payload
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     */
    public function replaceRelationship($resourceType, $resourceId, $relationship, array $payload, $parameters = []);

    /**
     * Replace the specified relationship for the record by serializing the related records.
     *
     * This request is valid for both a to-one and to-many relationship.
     *
     * For to-one relationships, the related argument can be:
     *
     * - An object.
     * - Null.
     *
     * For a to-many relationship, the related argument can be:
     *
     * - An array or iterable containing objects.
     * - An empty array or iterable (to clear the relationship).
     *
     * @param object $record
     *      the record on which the relationship is being replaced.
     * @param object|iterable|array|null $related
     *      the related record or record(s) to replace the relationship with.
     * @param string $relationship
     *      the field name for the relationship.
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function replaceRecordRelationship($record, $related, $relationship, $parameters = []);

    /**
     * Add-to the specified relationship.
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param string $relationship
     *      the field name for the relationship.
     * @param array $payload
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     */
    public function addToRelationship($resourceType, $resourceId, $relationship, array $payload, $parameters = []);

    /**
     * Add-to the specified relationship for the record by serializing the related records.
     *
     * @param object $record
     *      the record on which the relationship is being replaced.
     * @param iterable|array $related
     *      the related records to replace the relationship with.
     * @param string $relationship
     *      the field name for the relationship.
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function addToRecordRelationship($record, $related, $relationship, $parameters = []);

    /**
     * Remove-from the specified relationship.
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param string $relationship
     *      the field name for the relationship.
     * @param array $payload
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     */
    public function removeFromRelationship(
        $resourceType,
        $resourceId,
        $relationship,
        array $payload,
        $parameters = []
    );

    /**
     * Remove-from the specified relationship for the record by serializing the related records.
     *
     * @param object $record
     *      the record on which the relationship is being replaced.
     * @param iterable|array $related
     *      the related records to replace the relationship with.
     * @param string $relationship
     *      the field name for the relationship.
     * @param array|QueryParametersInterface $parameters
     * @return ResponseInterface
     * @throws ClientException
     */
    public function removeFromRecordRelationship($record, $related, $relationship, $parameters = []);
}
