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

use ArrayIterator;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectCollectionInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Exceptions\InvalidArgumentException;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;

/**
 * Class ResourceCollection
 *
 * @package CloudCreativity\JsonApi
 */
class ResourceObjectCollection implements ResourceObjectCollectionInterface
{

    /**
     * @var ResourceObjectInterface[]
     */
    private $stack = [];

    /**
     * @param array $resources
     * @return ResourceObjectCollection
     */
    public static function create(array $resources)
    {
        $resources = array_map(function ($resource) {
            return ($resource instanceof ResourceObjectInterface) ? $resource : new ResourceObject($resource);
        }, $resources);

        return new self($resources);
    }

    /**
     * ResourceCollection constructor.
     *
     * @param array $resources
     */
    public function __construct(array $resources = [])
    {
        $this->addMany($resources);
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return new ArrayIterator($this->stack);
    }

    /**
     * @inheritDoc
     */
    public function has(ResourceIdentifierInterface $identifier)
    {
        /** @var ResourceObjectInterface $resource */
        foreach ($this as $resource) {

            if ($identifier->isSame($resource->getIdentifier())) {
                return true;
            }
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function get(ResourceIdentifierInterface $identifier)
    {
        /** @var ResourceObjectInterface $resource */
        foreach ($this as $resource) {

            if ($identifier->isSame($resource->getIdentifier())) {
                return $resource;
            }
        }

        throw new RuntimeException('No matching resource in collection: ' . $identifier->toString());
    }

    /**
     * @inheritDoc
     */
    public function getAll()
    {
        return $this->stack;
    }

    /**
     * @inheritDoc
     */
    public function getIdentifiers()
    {
        $collection = new ResourceIdentifierCollection();

        /** @var ResourceObjectInterface $resource */
        foreach ($this as $resource) {
            $collection->add($resource->getIdentifier());
        }

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty()
    {
        return empty($this->stack);
    }

    /**
     * @inheritDoc
     */
    public function count()
    {
        return count($this->stack);
    }

    /**
     * @param ResourceObjectInterface $resource
     * @return $this
     */
    public function add(ResourceObjectInterface $resource)
    {
        if (!$this->has($resource->getIdentifier())) {
            $this->stack[] = $resource;
        }

        return $this;
    }

    /**
     * @param array $resources
     * @return $this
     */
    public function addMany(array $resources)
    {
        foreach ($resources as $resource) {

            if (!$resource instanceof ResourceObjectInterface) {
                throw new InvalidArgumentException('Expecting only resource objects.');
            }

            $this->add($resource);
        }

        return $this;
    }
}
