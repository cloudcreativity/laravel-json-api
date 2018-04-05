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

namespace CloudCreativity\JsonApi\Authorizer;

use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class ReadOnlyAuthorizer
 *
 * @package CloudCreativity\JsonApi
 */
class ReadOnlyAuthorizer extends AbstractAuthorizer
{

    /**
     * @inheritdoc
     */
    public function canReadMany($resourceType, EncodingParametersInterface $parameters)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canCreate($resourceType, ResourceObjectInterface $resource, EncodingParametersInterface $parameters)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function canRead($record, EncodingParametersInterface $parameters)
    {
        return true;
    }

    /**
     * @inheritdoc
     */
    public function canUpdate($record, ResourceObjectInterface $resource, EncodingParametersInterface $parameters)
    {
        return false;
    }

    /**
     * @inheritdoc
     */
    public function canDelete($record, EncodingParametersInterface $parameters)
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function canModifyRelationship(
        $relationshipKey,
        $record,
        RelationshipInterface $relationship,
        EncodingParametersInterface $parameters
    ) {
        return false;
    }

}
