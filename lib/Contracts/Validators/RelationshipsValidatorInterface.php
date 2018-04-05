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

namespace CloudCreativity\JsonApi\Contracts\Validators;

use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Contracts\Utils\ErrorsAwareInterface;

/**
 * Interface RelationshipsValidatorInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface RelationshipsValidatorInterface extends ErrorsAwareInterface
{

    /**
     * @param $key
     * @param RelationshipValidatorInterface $validator
     * @return $this
     */
    public function add($key, RelationshipValidatorInterface $validator);

    /**
     * @param $key
     * @return RelationshipValidatorInterface
     */
    public function get($key);

    /**
     * Add a has-one relationship validator for the specified relationship key.
     *
     * @param string $key
     *      the key of the relationship.
     * @param string|string[]|null $expectedType
     *      the expected type or types. If null, defaults to the key name.
     * @param bool $required
     *      must the relationship exist as a member on the relationship object?
     * @param bool $allowEmpty
     *      is an empty has-one relationship acceptable?
     * @param AcceptRelatedResourceInterface|callable|null $acceptable
     *      if a non-empty relationship that exists, is it acceptable?
     * @return $this
     */
    public function hasOne(
        $key,
        $expectedType = null,
        $required = false,
        $allowEmpty = true,
        $acceptable = null
    );

    /**
     * Add a has-many relationship validator for the specified relationship key.
     *
     * @param string $key
     *      the key of the relationship.
     * @param string|string[]|null $expectedType
     *      the expected type or types. If null, defaults to the key name.
     * @param bool $required
     *      must the relationship exist as a member on the relationship object?
     * @param bool $allowEmpty
     *      is an empty has-many relationship acceptable?
     * @param AcceptRelatedResourceInterface|callable|null $acceptable
     *      if an identifier exists, is it acceptable within this relationship?
     * @return $this
     */
    public function hasMany(
        $key,
        $expectedType = null,
        $required = false,
        $allowEmpty = false,
        $acceptable = null
    );

    /**
     * Are the relationships on the supplied resource valid?
     *
     * @param ResourceObjectInterface $resource
     * @param object|null $record
     *      the domain object that owns the relationships.
     * @return bool
     */
    public function isValid(ResourceObjectInterface $resource, $record = null);
}
