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

namespace CloudCreativity\JsonApi\Contracts\Http\Client;

use CloudCreativity\JsonApi\Contracts\Http\Responses\ResponseInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * Interface ClientInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface ClientInterface
{

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
     * @param string[]|null $fields
     *      the resource fields to send, if sending sparse field-sets.
     * @param EncodingParametersInterface|null $parameters
     *      the parameters to send to the remote server.
     * @param array $options
     * @return ResponseInterface
     * @throws JsonApiException
     *      if the remote server replies with an error.
     */
    public function update(
        $record,
        array $fields = null,
        EncodingParametersInterface $parameters = null,
        array $options = []
    );

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
