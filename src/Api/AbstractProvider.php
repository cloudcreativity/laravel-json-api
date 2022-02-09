<?php

/*
 * Copyright 2022 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Resolver\NamespaceResolver;
use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;

/**
 * Class ResourceProvider
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractProvider
{

    /**
     * @var array
     */
    protected $resources = [];

    /**
     * @var bool
     */
    protected $byResource = true;

    /**
     * Mount routes onto the provided API.
     *
     * @param RouteRegistrar $api
     * @return void
     */
    abstract public function mount(RouteRegistrar $api): void;

    /**
     * @return string
     */
    abstract protected function getRootNamespace(): string;

    /**
     * @return ResolverInterface
     */
    public function getResolver(): ResolverInterface
    {
        return new NamespaceResolver($this->getRootNamespace(), $this->resources, $this->byResource);
    }

}
