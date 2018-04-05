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
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\Utils\Object\StandardObject;

/**
 * Class Relationship
 *
 * @package CloudCreativity\JsonApi
 */
class Relationship extends StandardObject implements RelationshipInterface
{

    use MetaMemberTrait;

    /**
     * @inheritdoc
     */
    public function getData()
    {
        if ($this->isHasMany()) {
            return $this->getIdentifiers();
        } elseif (!$this->isHasOne()) {
            throw new RuntimeException('No data member or data member is not a valid relationship.');
        }

        return $this->hasIdentifier() ? $this->getIdentifier() : null;
    }


    /**
     * @inheritdoc
     */
    public function getIdentifier()
    {
        if (!$this->isHasOne()) {
            throw new RuntimeException('No data member or data member is not a valid has-one relationship.');
        }

        $data = $this->{self::DATA};

        if (!$data) {
            throw new RuntimeException('No resource identifier - relationship is empty.');
        }

        return new ResourceIdentifier($data);
    }

    /**
     * @inheritdoc
     */
    public function hasIdentifier()
    {
        return is_object($this->{self::DATA});
    }

    /**
     * @inheritdoc
     */
    public function isHasOne()
    {
        if (!$this->has(self::DATA)) {
            return false;
        }

        $data = $this->{self::DATA};

        return is_null($data) || is_object($data);
    }

    /**
     * @inheritdoc
     */
    public function getIdentifiers()
    {
        if (!$this->isHasMany()) {
            throw new RuntimeException('No data member of data member is not a valid has-many relationship.');
        }

        return ResourceIdentifierCollection::create($this->{self::DATA});
    }

    /**
     * @inheritdoc
     */
    public function isHasMany()
    {
        return is_array($this->{self::DATA});
    }
}
