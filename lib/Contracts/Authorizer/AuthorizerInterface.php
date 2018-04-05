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

namespace CloudCreativity\JsonApi\Contracts\Authorizer;

use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Contracts\Utils\ErrorsAwareInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Interface AuthorizerInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface AuthorizerInterface extends ErrorsAwareInterface
{

    /**
     * Can the client read many resources at once?
     *
     * Encoding parameters are provided in case the parameters such as
     * filtering or inclusion paths affect whether the resources can be read.
     *
     * @param string $resourceType
     *      the requested resource type.
     * @param EncodingParametersInterface $parameters
     *      the parameters provided by the client
     * @return bool
     */
    public function canReadMany($resourceType, EncodingParametersInterface $parameters);

    /**
     * Can the client create the provided resource?
     *
     * @param string $resourceType
     *      the resource type being created.
     * @param ResourceObjectInterface $resource
     *      the resource provided by the client.
     * @param EncodingParametersInterface $parameters
     *      the parameters provided by the client
     * @return bool
     */
    public function canCreate($resourceType, ResourceObjectInterface $resource, EncodingParametersInterface $parameters);

    /**
     * Can the client read the specified record?
     *
     * @param object $record
     *      the record that the client is trying to read.
     * @param EncodingParametersInterface $parameters
     *      the parameters provided by the client
     * @return bool
     */
    public function canRead($record, EncodingParametersInterface $parameters);

    /**
     * Can the client update the specified record?
     *
     * @param object $record
     *      the record that the client is trying to update.
     * @param ResourceObjectInterface $resource
     *      the resource provided by the client
     * @param EncodingParametersInterface $parameters
     *      the parameters provided by the client
     * @return bool
     */
    public function canUpdate($record, ResourceObjectInterface $resource, EncodingParametersInterface $parameters);

    /**
     * Can the client delete the specified record?
     *
     * @param object $record
     *      the record that the client is trying to delete.
     * @param EncodingParametersInterface $parameters
     *      the parameters provided by the client
     * @return bool
     */
    public function canDelete($record, EncodingParametersInterface $parameters);

    /**
     * Can the client read the related resource?
     *
     * @param $relationshipKey
     * @param $record
     * @param EncodingParametersInterface $parameters
     * @return bool
     */
    public function canReadRelatedResource($relationshipKey, $record, EncodingParametersInterface $parameters);

    /**
     * Can the client read the specified resource relationship?
     *
     * @param string $relationshipKey
     *      the relationship that the client is trying to read.
     * @param object $record
     *      the record to which the relationship relates.
     * @param EncodingParametersInterface $parameters
     *      the parameters provided by the client
     * @return bool
     */
    public function canReadRelationship($relationshipKey, $record, EncodingParametersInterface $parameters);

    /**
     * Can the client modified the specified resource relationship?
     *
     * @param string $relationshipKey
     * @param object $record
     *      the record that the client is attempting to modify the relationship on.
     * @param RelationshipInterface $relationship
     *      the relationship object provided by the client
     * @param EncodingParametersInterface $parameters
     *      the parameters provided by the client
     * @return bool
     * @see http://jsonapi.org/format/#crud-updating-relationships
     */
    public function canModifyRelationship(
        $relationshipKey,
        $record,
        RelationshipInterface $relationship,
        EncodingParametersInterface $parameters
    );

}
