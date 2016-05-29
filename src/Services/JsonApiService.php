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

namespace CloudCreativity\LaravelJsonApi\Services;

use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Contracts\Container\Container;

/**
 * Class JsonApiService
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiService
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var ResourceRegistrar
     */
    private $registrar;

    /**
     * JsonApiService constructor.
     * @param Container $container
     * @param ResourceRegistrar $registrar
     */
    public function __construct(Container $container, ResourceRegistrar $registrar)
    {
        $this->container = $container;
        $this->registrar = $registrar;
    }

    /**
     * @param $resourceType
     * @param $controller
     * @param array $options
     */
    public function resource($resourceType, $controller, array $options = [])
    {
        $this->registrar->resource($resourceType, $controller, $options);
    }

    /**
     * @return JsonApiContainer
     */
    public function container()
    {
        return $this->container->make(JsonApiContainer::class);
    }

    /**
     * @return bool
     */
    public function isJsonApi()
    {
        return $this->container->bound(JsonApiContainer::class);
    }
}
