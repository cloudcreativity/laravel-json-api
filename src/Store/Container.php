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

namespace CloudCreativity\LaravelJsonApi\Store;

use CloudCreativity\JsonApi\Contracts\Store\AdapterInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Store\Container as BaseContainer;
use Illuminate\Contracts\Container\Container as LaravelContainer;

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
     */
    public function __construct(LaravelContainer $container)
    {
        $this->container = $container;
    }

    /**
     * @param $string
     * @return AdapterInterface
     */
    protected function createFromString($string)
    {
        $adapter = $this->container->make($string);

        if (!$adapter instanceof AdapterInterface) {
            throw new RuntimeException("Service $string is not a JSON API adapter.");
        }

        return $adapter;
    }
}
