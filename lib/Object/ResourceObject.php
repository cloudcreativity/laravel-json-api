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

namespace CloudCreativity\JsonApi\Object;

use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\Utils\Object\StandardObject;
use CloudCreativity\Utils\Object\StandardObjectInterface;

/**
 * Class Resource
 *
 * @package CloudCreativity\JsonApi
 */
class ResourceObject extends StandardObject implements ResourceObjectInterface
{

    use IdentifiableTrait,
        MetaMemberTrait;

    /**
     * @inheritdoc
     */
    public function getIdentifier()
    {
        return ResourceIdentifier::create($this->getType(), $this->getId());
    }

    /**
     * @inheritdoc
     */
    public function getAttributes()
    {
        $attributes = $this->hasAttributes() ? $this->get(self::ATTRIBUTES) : new StandardObject();

        if (!$attributes instanceof StandardObjectInterface) {
            throw new RuntimeException('Attributes member is not an object.');
        }

        return $attributes;
    }

    /**
     * @inheritdoc
     */
    public function hasAttributes()
    {
        return $this->has(self::ATTRIBUTES);
    }

    /**
     * @inheritdoc
     */
    public function getRelationships()
    {
        $relationships = $this->hasRelationships() ? $this->{self::RELATIONSHIPS} : null;

        if (!is_null($relationships) && !is_object($relationships)) {
            throw new RuntimeException('Relationships member is not an object.');
        }

        return new Relationships($relationships);
    }

    /**
     * @inheritdoc
     */
    public function hasRelationships()
    {
        return $this->has(self::RELATIONSHIPS);
    }

    /**
     * @inheritDoc
     */
    public function getRelationship($key)
    {
        $relationships = $this->getRelationships();

        return $relationships->has($key) ? $relationships->getRelationship($key) : null;
    }

}
