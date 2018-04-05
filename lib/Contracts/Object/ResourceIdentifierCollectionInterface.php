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

use Countable;
use IteratorAggregate;

/**
 * Interface ResourceIdentifierCollectionInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface ResourceIdentifierCollectionInterface extends IteratorAggregate, Countable
{

    /**
     * Does the collection contain the supplied identifier?
     *
     * @param ResourceIdentifierInterface $identifier
     * @return bool
     */
    public function has(ResourceIdentifierInterface $identifier);

    /**
     * Get the collection as an array.
     *
     * @return ResourceIdentifierInterface[]
     */
    public function getAll();

    /**
     * Is the collection empty?
     *
     * @return bool
     */
    public function isEmpty();

    /**
     * Is every identifier in the collection complete?
     *
     * @return bool
     */
    public function isComplete();

    /**
     * Does every identifier in the collection match the supplied type/any of the supplied types?
     *
     * @param string|string[] $typeOrTypes
     * @return bool
     */
    public function isOnly($typeOrTypes);

    /**
     * Get an array of the ids of each identifier in the collection.
     *
     * @return array
     */
    public function getIds();

    /**
     * Map the collection to an array of type keys and id values.
     *
     * For example, this JSON structure:
     *
     * ```
     * [
     *  {"type": "foo", "id": "1"},
     *  {"type": "foo", "id": "2"},
     *  {"type": "bar", "id": "99"}
     * ]
     * ```
     *
     * Will map to:
     *
     * ```
     * [
     *  "foo" => ["1", "2"],
     *  "bar" => ["99"]
     * ]
     * ```
     *
     * If the method call is provided with the an array `['foo' => 'FooModel', 'bar' => 'FoobarModel']`, then the
     * returned mapped array will be:
     *
     * ```
     * [
     *  "FooModel" => ["1", "2"],
     *  "FoobarModel" => ["99"]
     * ]
     * ```
     *
     * @param string[]|null $typeMap
     *      if an array, map the identifier types to the supplied types.
     * @return mixed
     */
    public function map(array $typeMap = null);
}
