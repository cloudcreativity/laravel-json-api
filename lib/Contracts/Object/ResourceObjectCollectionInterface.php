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

namespace CloudCreativity\JsonApi\Contracts\Object;

use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Countable;
use IteratorAggregate;

/**
 * Interface ResourceIdentifierCollectionInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface ResourceObjectCollectionInterface extends IteratorAggregate, Countable
{

    /**
     * Does the collection contain a resource with the supplied identifier?
     *
     * @param ResourceIdentifierInterface $identifier
     * @return bool
     */
    public function has(ResourceIdentifierInterface $identifier);

    /**
     * Get the resource with the supplied identifier.
     *
     * @param ResourceIdentifierInterface $identifier
     * @return ResourceObjectInterface
     * @throws RuntimeException
     *      if the collection does not contain a resource that matches the supplied identifier.
     */
    public function get(ResourceIdentifierInterface $identifier);

    /**
     * Get the collection as an array.
     *
     * @return ResourceObjectInterface[]
     */
    public function getAll();

    /**
     * Get all the resource identifiers of the resources in the collection
     *
     * @return ResourceIdentifierCollectionInterface
     */
    public function getIdentifiers();

    /**
     * Is the collection empty?
     *
     * @return bool
     */
    public function isEmpty();

}
