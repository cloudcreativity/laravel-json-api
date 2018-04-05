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

use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\Utils\Object\StandardObject;

/**
 * Class ResourceIdentifier
 *
 * @package CloudCreativity\JsonApi
 */
class ResourceIdentifier extends StandardObject implements ResourceIdentifierInterface
{

    use IdentifiableTrait,
        MetaMemberTrait;

    /**
     * @param $type
     * @param $id
     * @return ResourceIdentifier
     */
    public static function create($type, $id)
    {
        $identifier = new self();

        $identifier->set(self::TYPE, $type)
            ->set(self::ID, $id);

        return $identifier;
    }

    /**
     * @inheritDoc
     */
    public function isType($typeOrTypes)
    {
        return in_array($this->get(self::TYPE), (array) $typeOrTypes, true);
    }

    /**
     * @inheritDoc
     */
    public function mapType(array $map)
    {
        $type = $this->getType();

        if (array_key_exists($type, $map)) {
            return $map[$type];
        }

        throw new RuntimeException(sprintf('Type "%s" is not in the supplied map.', $type));
    }

    /**
     * @inheritDoc
     */
    public function isComplete()
    {
        return $this->hasType() && $this->hasId();
    }

    /**
     * @inheritDoc
     */
    public function isSame(ResourceIdentifierInterface $identifier)
    {
        return $this->getType() === $identifier->getType() &&
            $this->getId() === $identifier->getId();
    }

    /**
     * @inheritDoc
     */
    public function toString()
    {
        return sprintf('%s:%s', $this->getType(), $this->getId());
    }

}
