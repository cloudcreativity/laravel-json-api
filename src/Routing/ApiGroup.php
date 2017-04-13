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

namespace CloudCreativity\LaravelJsonApi\Routing;

use CloudCreativity\LaravelJsonApi\Api\ApiResource;
use CloudCreativity\LaravelJsonApi\Api\Definition;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\Fluent;

/**
 * Class ApiGroup
 *
 * @package CloudCreativity\LaravelJsonApi\Routing
 */
class ApiGroup
{

    /**
     * @var Registrar
     */
    private $router;

    /**
     * @var Definition
     */
    private $apiDefinition;

    /**
     * @var Fluent
     */
    private $options;

    /**
     * ApiGroup constructor.
     *
     * @param Registrar $router
     * @param Definition $apiDefinition
     * @param array $options
     */
    public function __construct(Registrar $router, Definition $apiDefinition, array $options)
    {
        $this->router = $router;
        $this->apiDefinition = $apiDefinition;
        $this->options = new Fluent($options);
    }

    /**
     * Register routes for the supplied resource type
     *
     * @param string $resourceType
     * @param array $options
     * @return void
     */
    public function resource($resourceType, array $options = [])
    {
        $options = $this->normalizeOptions($options);

        $this->resourceGroup($resourceType, $options)->addResource($this->router);
    }

    /**
     * @param $resourceType
     * @param array $options
     * @return ResourceGroup
     */
    protected function resourceGroup($resourceType, array $options)
    {
        return new ResourceGroup($resourceType, $this->apiResource($resourceType), new Fluent($options));
    }

    /**
     * @param array $resourceOptions
     * @return array
     */
    protected function normalizeOptions(array $resourceOptions)
    {
        return array_merge($this->resourceDefaults(), $resourceOptions);
    }

    /**
     * @return array
     */
    protected function resourceDefaults()
    {
        return [
            'default-authorizer' => $this->options->get('authorizer'),
        ];
    }

    /**
     * @param $resourceType
     * @return ApiResource
     */
    protected function apiResource($resourceType)
    {
        return $this->apiDefinition->getResources()->get($resourceType);
    }
}
