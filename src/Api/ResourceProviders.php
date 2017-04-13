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

use CloudCreativity\LaravelJsonApi\Factories\Factory;
use IteratorAggregate;

/**
 * Class ResourceProviders
 *
 * @package CloudCreativity\LaravelJsonApi\Api
 */
class ResourceProviders implements IteratorAggregate
{

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var string[]
     */
    private $providers;

    /**
     * ResourceProviders constructor.
     *
     * @param Factory $factory
     * @param string[] $providers
     */
    public function __construct(Factory $factory, array $providers)
    {
        $this->factory = $factory;
        $this->providers = $providers;
    }

    /**
     * @param Definition $definition
     */
    public function registerAll(Definition $definition)
    {
        /** @var ResourceProvider $provider */
        foreach ($this as $provider) {
            $definition->register($provider);
        }
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        foreach ($this->providers as $provider) {
            yield $provider => $this->factory->createResourceProvider($provider);
        }
    }

}
