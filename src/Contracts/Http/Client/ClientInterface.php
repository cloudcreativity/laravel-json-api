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

namespace CloudCreativity\LaravelJsonApi\Contracts\Http\Client;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Responses\ResponseInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;

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
     * @param string $resourceType
     * @param EncodingParametersInterface|null $parameters
     *      the parameters to send to the remote server.
     * @param array $options
     * @return ResponseInterface
     * @throws JsonApiException
     *      if the remote server replies with an error.
     */
    public function index($resourceType, EncodingParametersInterface $parameters = null, array $options = []);

    /**
     * Send the domain record to the remote JSON API.
     *
     * @param object $record
     *      the resource fields to send, if sending sparse field-sets.
     * @param EncodingParametersInterface|null $parameters
     *      the parameters to send to the remote server.
     * @param array $options
     * @return ResponseInterface
     * @throws JsonApiException
     *      if the remote server replies with an error.
     */
    public function create($record, EncodingParametersInterface $parameters = null, array $options = []);

    /**
     * Read the domain record from the remote JSON API.
     *
     * @param string $resourceType
     * @param string $resourceId
     * @param EncodingParametersInterface|null $parameters
     *      the parameters to send to the remote server.
     * @param array $options
     * @return ResponseInterface
     * @throws JsonApiException
     *      if the remote server replies with an error.
     */
    public function read(
        $resourceType,
        $resourceId,
        EncodingParametersInterface $parameters = null,
        array $options = []
    );

    /**
     * Update the domain record with the remote JSON API.
     *
     * @param object $record
     * @param EncodingParametersInterface|null $parameters
     *      the parameters to send to the remote server.
     * @param array $options
     * @return ResponseInterface
     * @throws JsonApiException
     *      if the remote server replies with an error.
     */
    public function update($record, EncodingParametersInterface $parameters = null, array $options = []);

    /**
     * Delete the domain record from the remote JSON API.
     *
     * @param object $record
     * @param array $options
     * @return ResponseInterface
     * @throws JsonApiException
     *      if the remote server replies with an error.
     */
    public function delete($record, array $options = []);
}
