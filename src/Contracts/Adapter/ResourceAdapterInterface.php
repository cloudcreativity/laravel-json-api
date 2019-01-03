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

namespace CloudCreativity\LaravelJsonApi\Contracts\Adapter;

use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Interface ResourceAdapterInterface
 *
 * Adapters are responsible for converting JSON API queries or resource identifiers into domain
 * record(s). Adapters are attached to a store via the adapter container. This allows a JSON API
 * store to query different types of domain records regardless of how these are actually stored
 * and retrieved within an application.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface ResourceAdapterInterface
{

    /**
     * Query many domain records.
     *
     * @param EncodingParametersInterface $parameters
     * @return mixed
     */
    public function query(EncodingParametersInterface $parameters);

    /**
     * Create a domain record using data from the supplied resource object.
     *
     * @param array $document
     *      The JSON API document received from the client.
     * @param EncodingParametersInterface $parameters
     * @return AsynchronousProcess|mixed
     *      the created domain record, or the process to create it.
     */
    public function create(array $document, EncodingParametersInterface $parameters);

    /**
     * Query a single domain record.
     *
     * @param mixed $record
     *      the domain record being read.
     * @param EncodingParametersInterface $parameters
     * @return mixed|null
     */
    public function read($record, EncodingParametersInterface $parameters);

    /**
     * Update a domain record with data from the supplied resource object.
     *
     * @param mixed $record
     *      the domain record to update.
     * @param array $document
     *      The JSON API document received from the client.
     * @param EncodingParametersInterface $params
     * @return AsynchronousProcess|mixed
     *      the updated domain record or the process to updated it.
     */
    public function update($record, array $document, EncodingParametersInterface $params);

    /**
     * Delete a domain record.
     *
     * @param mixed $record
     * @param EncodingParametersInterface $params
     * @return AsynchronousProcess|bool
     *      whether the record was successfully destroyed, or the process to delete it.
     */
    public function delete($record, EncodingParametersInterface $params);

    /**
     * Does a domain record of the specified JSON API resource id exist?
     *
     * @param string $resourceId
     * @return bool
     */
    public function exists($resourceId);

    /**
     * Get the domain record that relates to the specified JSON API resource id, if it exists.
     *
     * @param string $resourceId
     * @return mixed|null
     */
    public function find($resourceId);

    /**
     * Find many domain records for the specified JSON API resource ids.
     *
     * The returned collection MUST NOT contain any duplicate domain records, and MUST only contain
     * domain records that match the supplied resource ids. A collection MUST be returned even if some
     * or all of the resource IDs cannot be converted into domain records - i.e. the returned collection
     * may contain less domain records than the supplied number of ids.
     *
     * @param array $resourceIds
     * @return array
     */
    public function findMany(array $resourceIds);

    /**
     * Get the relationship adapter for the specified relationship.
     *
     * @param $field
     * @return RelationshipAdapterInterface
     */
    public function getRelated($field);

}
