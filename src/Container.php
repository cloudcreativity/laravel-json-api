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

namespace CloudCreativity\LaravelJsonApi;

use CloudCreativity\JsonApi\AbstractContainer;
use CloudCreativity\JsonApi\Contracts\Resolver\ResolverInterface;
use Illuminate\Contracts\Container\Container as IlluminateContainer;

/**
 * Class Container
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Container extends AbstractContainer
{

    /**
     * @var IlluminateContainer
     */
    private $container;

    /**
     * Container constructor.
     *
     * @param IlluminateContainer $container
     * @param ResolverInterface $resolver
     */
    public function __construct(
        IlluminateContainer $container,
        ResolverInterface $resolver
    ) {
        parent::__construct($resolver);
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    protected function create($className)
    {
        if (!class_exists($className)) {
            return null;
        }

        return $this->container->make($className);
    }

}
