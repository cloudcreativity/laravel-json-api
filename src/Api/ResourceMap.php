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

use IteratorAggregate;

/**
 * Class ResourceMap
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceMap implements IteratorAggregate
{

    /**
     * @var string
     */
    private $rootNamespace;

    /**
     * @var array
     */
    private $resources;

    /**
     * @var bool
     */
    private $byResource;

    /**
     * ResourceMap constructor.
     *
     * @param string $rootNamespace
     * @param array $resources
     * @param bool $byResource
     */
    public function __construct($rootNamespace, array $resources, $byResource = true)
    {
        $this->rootNamespace = $rootNamespace;
        $this->resources = $resources;
        $this->byResource = $byResource;
    }

    /**
     * @return string
     */
    public function getRootNamespace()
    {
        return $this->rootNamespace;
    }

    /**
     * @return bool
     */
    public function isByResource()
    {
        return $this->byResource;
    }

    /**
     * @return ApiResources
     */
    public function all()
    {
        return new ApiResources(iterator_to_array($this));
    }

    /**
     * @inheritdoc
     */
    public function getIterator()
    {
        foreach ($this->resources as $type => $fqn) {
            yield $type => new ApiResource($type, $fqn, $this->rootNamespace, $this->byResource);
        }
    }
}
