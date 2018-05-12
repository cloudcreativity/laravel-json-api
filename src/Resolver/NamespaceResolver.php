<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Resolver;

use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Utils\Str;

/**
 * Class NamespaceResolver
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class NamespaceResolver implements ResolverInterface
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
     * @var array
     */
    private $types;

    /**
     * @var bool
     */
    private $byResource;

    /**
     * NamespaceResolver constructor.
     *
     * @param string $rootNamespace
     * @param array $resources
     * @param bool $byResource
     */
    public function __construct($rootNamespace, array $resources, $byResource = true)
    {
        $this->rootNamespace = $rootNamespace;
        $this->resources = $resources;
        $this->types = $this->flip($resources);
        $this->byResource = $byResource;
    }

    /**
     * @inheritDoc
     */
    public function isType($type)
    {
        return isset($this->types[$type]);
    }

    /**
     * @inheritdoc
     */
    public function getType($resourceType)
    {
        if (!isset($this->resources[$resourceType])) {
            return null;
        }

        return $this->resources[$resourceType];
    }

    /**
     * @inheritDoc
     */
    public function getAllTypes()
    {
        return array_keys($this->types);
    }


    /**
     * @inheritDoc
     */
    public function isResourceType($resourceType)
    {
        return isset($this->resources[$resourceType]);
    }

    /**
     * @inheritdoc
     */
    public function getResourceType($type)
    {
        if (!isset($this->types[$type])) {
            return null;
        }

        return $this->types[$type];
    }

    /**
     * @inheritDoc
     */
    public function getAllResourceTypes()
    {
        return array_keys($this->resources);
    }

    /**
     * @inheritdoc
     */
    public function getSchemaByType($type)
    {
        $resourceType = $this->getResourceType($type);

        return $resourceType ? $this->getSchemaByResourceType($resourceType) : null;
    }

    /**
     * @inheritdoc
     */
    public function getSchemaByResourceType($resourceType)
    {
        return $this->resolve('Schema', $resourceType);
    }

    /**
     * @inheritdoc
     */
    public function getAdapterByType($type)
    {
        $resourceType = $this->getResourceType($type);

        return $resourceType ? $this->getAdapterByResourceType($resourceType) : null;
    }

    /**
     * @inheritdoc
     */
    public function getAdapterByResourceType($resourceType)
    {
        return $this->resolve('Adapter', $resourceType);
    }

    /**
     * @inheritdoc
     */
    public function getAuthorizerByType($type)
    {
        $resourceType = $this->getResourceType($type);

        return $resourceType ? $this->getAuthorizerByResourceType($resourceType) : null;
    }

    /**
     * @inheritdoc
     */
    public function getAuthorizerByResourceType($resourceType)
    {
        return $this->resolve('Authorizer', $resourceType);
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizerByName($name)
    {
        if (!$this->byResource) {
            return $this->resolve('Authorizer', $name);
        }

        $classified = Str::classify($name);

        return $this->append("{$classified}Authorizer");
    }

    /**
     * @inheritdoc
     */
    public function getValidatorsByType($type)
    {
        $resourceType = $this->getResourceType($type);

        return $resourceType ? $this->getValidatorsByResourceType($resourceType) : null;
    }

    /**
     * @inheritdoc
     */
    public function getValidatorsByResourceType($resourceType)
    {
        return $this->resolve('Validators', $resourceType);
    }

    /**
     * Convert the provided unit name and resource type into a fully qualified namespace.
     *
     * @param $unit
     * @param $resourceType
     * @return string
     */
    protected function resolve($unit, $resourceType)
    {
        $classified = Str::classify($resourceType);

        if ($this->byResource) {
            return $this->append($classified . '\\' . $unit);
        }

        return $this->append(sprintf('%s\%s', str_plural($unit), str_singular($classified)));
    }

    /**
     * Append the string to the root namespace.
     *
     * @param $string
     * @return string
     */
    protected function append($string)
    {
        return sprintf('%s\%s', rtrim($this->rootNamespace, '\\'), $string);
    }

    /**
     * Key the resource array by domain record type.
     *
     * @param array $resources
     * @return array
     */
    private function flip(array $resources)
    {
        $all = [];

        foreach ($resources as $resourceType => $types) {
            foreach ((array) $types as $type) {
                $all[$type] = $resourceType;
            }
        }

        return $all;
    }
}
