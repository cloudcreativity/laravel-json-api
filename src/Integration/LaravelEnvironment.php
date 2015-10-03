<?php

/**
 * Copyright 2015 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Integration;

use CloudCreativity\JsonApi\Integration\EnvironmentService as BaseService;
use CloudCreativity\JsonApi\Routing\ResourceRegistrar;
use Neomerx\JsonApi\Contracts\Integration\CurrentRequestInterface;
use Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;
use Neomerx\JsonApi\Contracts\Parameters\ParametersFactoryInterface;

/**
 * Class LaravelEnvironment
 * @package CloudCreativity\JsonApi\Laravel
 */
class LaravelEnvironment extends BaseService
{

    /**
     * @var ResourceRegistrar
     */
    private $resourceRegistrar;

    /**
     * @param ParametersFactoryInterface $factory
     * @param CurrentRequestInterface $currentRequest
     * @param ExceptionThrowerInterface $exceptionThrower
     * @param ResourceRegistrar $resourceRegistrar
     */
    public function __construct(
        ParametersFactoryInterface $factory,
        CurrentRequestInterface $currentRequest,
        ExceptionThrowerInterface $exceptionThrower,
        ResourceRegistrar $resourceRegistrar
    ) {
        parent::__construct($factory, $currentRequest, $exceptionThrower);
        $this->resourceRegistrar = $resourceRegistrar;
    }

    /**
     * @param $name
     * @param $controller
     * @param array $options
     */
    public function resource($name, $controller, array $options = [])
    {
        $this->resourceRegistrar->resource($name, $controller, $options);
    }
}
