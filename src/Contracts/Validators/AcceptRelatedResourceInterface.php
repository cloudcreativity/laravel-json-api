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

namespace CloudCreativity\LaravelJsonApi\Contracts\Validators;

use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * Interface AcceptRelatedResourceInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0 use classes in the `Validation` namespace instead.
 */
interface AcceptRelatedResourceInterface
{

    /**
     * Is the specified resource identifier acceptable?
     *
     * @param ResourceIdentifierInterface $identifier
     *      the identifier being validated.
     * @param object|null $record
     *      the domain object that owns the relationship.
     * @param string|null $key
     *      if validating a resource's relationships, the key that is being validated.
     * @param ResourceObjectInterface|null $resource
     *      if validating a resource's relationships, the resource for context.
     * @return bool|ErrorInterface|ErrorCollection
     */
    public function accept(
        ResourceIdentifierInterface $identifier,
        $record = null,
        $key = null,
        ResourceObjectInterface $resource = null
    );
}
