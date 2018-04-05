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

namespace CloudCreativity\JsonApi\Store;

use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\JsonApi\Exceptions\InvalidArgumentException;

/**
 * Class IdentityMap
 *
 * @package CloudCreativity\JsonApi
 */
class IdentityMap
{

    /**
     * @var array
     */
    private $map = [];

    /**
     * Add a record to the identity map for a resource identifier.
     *
     * The record can either be a boolean (the result of a store's `exists()` check), or the actual
     * record itself. However, a boolean cannot be inserted into the map if the map already holds the
     * record itself.
     *
     * @param ResourceIdentifierInterface $identifier
     * @param object|bool $record
     * @return $this
     */
    public function add(ResourceIdentifierInterface $identifier, $record)
    {
        if (!is_object($record) && !is_bool($record)) {
            throw new InvalidArgumentException('Expecting an object or a boolean to add to the identity map.');
        }

        $existing = $this->lookup($identifier);

        if (is_object($existing) && is_bool($record)) {
            throw new InvalidArgumentException('Attempting to push a boolean into the map in place of an object.');
        }

        $this->map[$identifier->toString()] = $record;

        return $this;
    }

    /**
     * Does the identity map know that ths supplied identifier exists?
     *
     * @param ResourceIdentifierInterface $identifier
     * @return bool|null
     *      the answer, or null if the identity map does not know
     */
    public function exists(ResourceIdentifierInterface $identifier)
    {
        $record = $this->lookup($identifier);

        return is_object($record) ? true : $record;
    }

    /**
     * Get the record from the identity map.
     *
     * @param ResourceIdentifierInterface $identifier
     * @return object|bool|null
     *      the record, false if it is known not to exist, or null if the identity map does not have the object.
     */
    public function find(ResourceIdentifierInterface $identifier)
    {
        $record = $this->lookup($identifier);

        if (false === $record) {
            return false;
        }

        return is_object($record) ? $record : null;
    }

    /**
     * @param ResourceIdentifierInterface $identifier
     * @return object|bool|null
     */
    private function lookup(ResourceIdentifierInterface $identifier)
    {
        $key = $identifier->toString();

        return isset($this->map[$key]) ? $this->map[$key] : null;
    }
}
