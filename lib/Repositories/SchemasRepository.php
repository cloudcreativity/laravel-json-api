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

namespace CloudCreativity\JsonApi\Repositories;

use CloudCreativity\JsonApi\Contracts\Repositories\SchemasRepositoryInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Neomerx\JsonApi\Factories\Factory;

/**
 * Class SchemasRepository
 *
 * @package CloudCreativity\JsonApi
 * @deprecated
 *
 * Example provided config array:
 *
 * ````
 * [
 *      'defaults' => [
 *          'Author' => 'AuthorSchema',
 *          'Post' => 'PostSchema',
 *      ],
 *      'foo' => [
 *           'Comment' => 'CommentSchema',
 *      ],
 * ]
 * ````
 *
 * If the 'foo' schema is requested, the return array will have Author, Schema and Comment in it.
 *
 * This repository also accepts non-namespaced schemas. I.e. if the config array does not have a 'defaults' key, it
 * will be loaded as the default schemas.
 */
class SchemasRepository implements SchemasRepositoryInterface
{

    /**
     * @var SchemaFactoryInterface
     */
    private $factory;

    /**
     * @var bool
     */
    private $namespaced = false;

    /**
     * @var array
     */
    private $schemas = [];

    /**
     * @param SchemaFactoryInterface|null $factory
     */
    public function __construct(SchemaFactoryInterface $factory = null)
    {
        $this->factory = $factory ?: new Factory();
    }

    /**
     * @param string $name
     * @return ContainerInterface
     */
    public function getSchemas($name = null)
    {
        $name = ($name) ?: static::DEFAULTS;

        if (static::DEFAULTS !== $name && !$this->namespaced) {
            throw new \RuntimeException(sprintf('Schemas configuration is not namespaced, so cannot get "%s".', $name));
        }

        $defaults = $this->get(static::DEFAULTS);
        $schemas = (static::DEFAULTS === $name) ? $defaults : array_merge($defaults, $this->get($name));

        return $this->factory->createContainer($schemas);
    }

    /**
     * @param array $config
     * @return $this
     */
    public function configure(array $config)
    {
        if (!isset($config[static::DEFAULTS])) {
            $config = [static::DEFAULTS => $config];
            $this->namespaced = false;
        } else {
            $this->namespaced = true;
        }

        $this->schemas = $config;

        return $this;
    }

    /**
     * @param $key
     * @return array
     */
    private function get($key)
    {
        return array_key_exists($key, $this->schemas) ? (array) $this->schemas[$key] : [];
    }

}
