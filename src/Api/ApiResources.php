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

namespace CloudCreativity\LaravelJsonApi\Api;

use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Illuminate\Support\Collection;
use IteratorAggregate;

/**
 * Class ApiResources
 *
 * @package CloudCreativity\LaravelJsonApi
 */
final class ApiResources implements IteratorAggregate
{

    /**
     * @var Collection
     */
    private $resources;

    /**
     * ApiResources constructor.
     *
     * @param array $resources
     */
    public function __construct(array $resources = [])
    {
        $this->resources = new Collection();
        $this->addMany($resources);
    }

    /**
     * @return void
     */
    public function __clone()
    {
        $this->resources = clone $this->resources;
    }

    /**
     * @param ApiResource $resource
     * @return $this
     */
    public function add(ApiResource $resource)
    {
        $this->resources[$resource->getResourceType()] = $resource;

        return $this;
    }

    /**
     * @param array $resources
     * @return $this
     */
    public function addMany(array $resources)
    {
        foreach ($resources as $resource) {
            $this->add($resource);
        }

        return $this;
    }

    /**
     * @param $resourceType
     * @return ApiResource
     */
    public function get($resourceType)
    {
        if (!$resource = $this->resources->get($resourceType)) {
            throw new RuntimeException("Resource type $resourceType does not exist.");
        }

        return $resource;
    }

    /**
     * @param $resourceType
     * @return bool
     */
    public function has($resourceType)
    {
        return $this->resources->has($resourceType);
    }

    /**
     * @return array
     */
    public function getSchemas()
    {
        $schemas = [];

        /** @var ApiResource $resource */
        foreach ($this as $resource) {
            $schemas[$resource->getRecordFqn()] = $resource->getSchemaFqn();
        }

        return $schemas;
    }

    /**
     * @return array
     */
    public function getAdapters()
    {
        return $this->resources->map(function (ApiResource $resource) {
            return $resource->getAdapterFqn();
        })->all();
    }

    /**
     * @param ApiResources $other
     * @return ApiResources
     */
    public function merge(self $other)
    {
        $resources = clone $this;
        $resources->merge($other);

        return $resources;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        return $this->resources->getIterator();
    }

}
