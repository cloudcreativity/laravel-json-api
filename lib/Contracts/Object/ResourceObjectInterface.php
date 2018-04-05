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
 * Interface ResourceObjectInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface ResourceObjectInterface extends StandardObjectInterface, MetaMemberInterface
{

    const TYPE = NeomerxDocumentInterface::KEYWORD_TYPE;
    const ID = NeomerxDocumentInterface::KEYWORD_ID;
    const ATTRIBUTES = NeomerxDocumentInterface::KEYWORD_ATTRIBUTES;
    const RELATIONSHIPS = NeomerxDocumentInterface::KEYWORD_RELATIONSHIPS;
    const META = NeomerxDocumentInterface::KEYWORD_META;

    /**
     * Get the type member.
     *
     * @return string
     * @throws RuntimeException
     *      if no type is set, is empty or is not a string.
     */
    public function getType();

    /**
     * @return string|int
     * @throws RuntimeException
     *      if no id is set, is not a string or integer, or is an empty string.
     */
    public function getId();

    /**
     * @return bool
     */
    public function hasId();

    /**
     * Get the type and id members as a resource identifier object.
     *
     * @return ResourceIdentifierInterface
     * @throws RuntimeException
     *      if the type and/or id members are not valid.
     */
    public function getIdentifier();

    /**
     * @return StandardObjectInterface
     * @throws RuntimeException
     *      if the attributes member is present and is not an object.
     */
    public function getAttributes();

    /**
     * @return bool
     */
    public function hasAttributes();

    /**
     * @return RelationshipsInterface
     * @throws RuntimeException
     *      if the relationships member is present and is not an object.
     */
    public function getRelationships();

    /**
     * @return bool
     */
    public function hasRelationships();

    /**
     * Get a relationship object by its key.
     *
     * @param string $key
     * @return RelationshipInterface|null
     *      the relationship object or null if it is not present.
     * @throws RuntimeException
     */
    public function getRelationship($key);

}
