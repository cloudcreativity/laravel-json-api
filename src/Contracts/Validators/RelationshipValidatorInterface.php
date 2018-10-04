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

namespace CloudCreativity\LaravelJsonApi\Contracts\Validators;

use CloudCreativity\LaravelJsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Utils\ErrorsAwareInterface;

/**
 * Interface RelationshipValidatorInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0 use classes in the `Validation` namespace instead.
 */
interface RelationshipValidatorInterface extends ErrorsAwareInterface
{

    /**
     * Is the provided relationship valid?
     *
     * @param RelationshipInterface $relationship
     * @param object|null
     *      the domain object that owns the relationships.
     * @param string|null $key
     *      if a full resource is being validated, the key of the relationship.
     * @param ResourceObjectInterface|null $resource
     *      if a full resource is being validated, the resource for context.
     * @return bool
     */
    public function isValid(
        RelationshipInterface $relationship,
        $record = null,
        $key = null,
        ResourceObjectInterface $resource = null
    );

}
