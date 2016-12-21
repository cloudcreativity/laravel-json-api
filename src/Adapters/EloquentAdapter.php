<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Adapters;

use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\JsonApi\Contracts\Store\AdapterInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EloquentAdapter
 * @package CloudCreativity\LaravelJsonApi
 */
class EloquentAdapter implements AdapterInterface
{

    /**
     * @var array
     */
    private $map;

    /**
     * @var array
     */
    private $keyNames;

    /**
     * EloquentAdapter constructor.
     * @param array $map
     *      a map of resource types to fully qualified Eloquent model classes.
     * @param array $keyNames
     *      a map of resource types to the model key name overrides - see `resolveKeyName()`
     */
    public function __construct(array $map = [], array $keyNames = [])
    {
        $this->map = $map;
        $this->keyNames = $keyNames;
    }

    /**
     * Is this adapter responsible for the supplied resource type?
     *
     * @param $resourceType
     * @return bool
     */
    public function recognises($resourceType)
    {
        return array_key_exists($resourceType, $this->map);
    }

    /**
     * @param ResourceIdentifierInterface $identifier
     * @return bool
     */
    public function exists(ResourceIdentifierInterface $identifier)
    {
        $model = $this->resolve($identifier->getType());
        $key = $this->resolveQualifiedKeyName($model, $identifier->getType());

        return $this->newQuery($model)->where($key, $identifier->getId())->exists();
    }

    /**
     * @param ResourceIdentifierInterface $identifier
     * @return object|null
     *      the record, or null if it does not exist.
     */
    public function find(ResourceIdentifierInterface $identifier)
    {
        $model = $this->resolve($identifier->getType());
        $key = $this->resolveQualifiedKeyName($model, $identifier->getType());

        return $this->newQuery($model)->where($key, $identifier->getId())->first();
    }

    /**
     * @param Model $model
     * @return Builder
     */
    protected function newQuery(Model $model)
    {
        return $model->newQuery();
    }

    /**
     * @param $resourceType
     * @return Model
     */
    protected function resolve($resourceType)
    {
        $fqn = $this->lookup($resourceType);
        $model = new $fqn();

        if (!$model instanceof Model) {
            throw new RuntimeException("Resource type $resourceType does not resolve to an Eloquent model.");
        }

        return $model;
    }

    /**
     * @param $resourceType
     * @return string
     */
    protected function lookup($resourceType)
    {
        if (!isset($this->map[$resourceType])) {
            throw new RuntimeException("Resource type $resourceType is not recognised.");
        }

        $fqn = $this->map[$resourceType];

        if (!is_string($fqn) || !class_exists($fqn)) {
            throw new RuntimeException("Class name for resource type $resourceType is not a valid class.");
        }

        return $fqn;
    }

    /**
     * @param Model $model
     * @param $resourceType
     * @return string
     */
    protected function resolveKeyName(Model $model, $resourceType)
    {
        return isset($this->keyNames[$resourceType]) ?
            $this->keyNames[$resourceType] : $model->getKeyName();
    }

    /**
     * Get the key name to use when querying for records.
     *
     * If no key name has been specified for the model, `Model::getQualifiedKeyName()` will be used as the
     * default.
     *
     * @param Model $model
     * @param $resourceType
     * @return string
     */
    protected function resolveQualifiedKeyName(Model $model, $resourceType)
    {
        return isset($this->keyNames[$resourceType]) ?
            sprintf('%s.%s', $model->getTable(), $this->keyNames[$resourceType]) :
            $model->getQualifiedKeyName();
    }
}
