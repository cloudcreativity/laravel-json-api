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

namespace CloudCreativity\LaravelJsonApi\Schema;

use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Illuminate\Contracts\Container\Container as LaravelContainer;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaProviderInterface;
use Neomerx\JsonApi\Schema\Container as BaseContainer;

/**
 * Class Container
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Container extends BaseContainer
{

    /**
     * @var LaravelContainer
     */
    private $container;

    /**
     * Container constructor.
     *
     * @param LaravelContainer $container
     * @param SchemaFactoryInterface $factory
     * @param array $schemas
     */
    public function __construct(LaravelContainer $container, SchemaFactoryInterface $factory, array $schemas = [])
    {
        parent::__construct($factory, $schemas);
        $this->container = $container;
    }

    /**
     * @param string $className
     * @return SchemaProviderInterface
     */
    protected function createSchemaFromClassName($className)
    {
        $schema = $this->container->make($className);

        if (!$schema instanceof SchemaProviderInterface) {
            throw new RuntimeException("Service $className is not a schema.");
        }

        return $schema;
    }
}
