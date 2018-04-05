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
use CloudCreativity\Utils\Object\StandardObjectInterface;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface as NeomerxDocumentInterface;

/**
 * Interface ResourceIdentifierInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface ResourceIdentifierInterface extends StandardObjectInterface, MetaMemberInterface
{

    const TYPE = NeomerxDocumentInterface::KEYWORD_TYPE;
    const ID = NeomerxDocumentInterface::KEYWORD_ID;
    const META = NeomerxDocumentInterface::KEYWORD_META;

    /**
     * @return string
     * @throws RuntimeException
     *      if the type member is not present, or is not a string, or is an empty string.
     */
    public function getType();

    /**
     * @return bool
     */
    public function hasType();

    /**
     * Returns true if the current type matches the supplied type, or any of the supplied types.
     *
     * @param string|string[] $typeOrTypes
     * @return bool
     */
    public function isType($typeOrTypes);

    /**
     * From the supplied array, return the value where the current type is the key.
     *
     * @param array $types
     * @return mixed
     * @throws RuntimeException
     *      if the current type is not one of those in the supplied $types
     */
    public function mapType(array $types);

    /**
     * @return string
     * @throws RuntimeException
     *      if the id member is not present, or is not a string, or is an empty string.
     */
    public function getId();

    /**
     * @return bool
     */
    public function hasId();

    /**
     * Whether both a type and an id are present.
     *
     * @return bool
     */
    public function isComplete();

    /**
     * Do the type and id match?
     *
     * @param ResourceIdentifierInterface $identifier
     * @return bool
     */
    public function isSame(ResourceIdentifierInterface $identifier);

    /**
     * Get a string representation of the identifier.
     *
     * @return string
     */
    public function toString();

}
