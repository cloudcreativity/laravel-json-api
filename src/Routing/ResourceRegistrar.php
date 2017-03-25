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

use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\Fluent;

/**
 * Class ResourceRegistrar
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceRegistrar
{

    const KEYWORD_RELATIONSHIPS = 'relationships';
    const PARAM_RESOURCE_TYPE = 'resource_type';
    const PARAM_RESOURCE_ID = 'resource_id';
    const PARAM_RELATIONSHIP_NAME = 'relationship_name';

    /**
     * @var Registrar
     */
    protected $router;

    /**
     * @param Registrar $router
     */
    public function __construct(Registrar $router)
    {
        $this->router = $router;
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
        $this->resourceGroup($resourceType, $options)
            ->addResource($this->router);
    }

    /**
     * @param $resourceType
     * @param array $options
     * @return ResourceGroup
     */
    protected function resourceGroup($resourceType, array $options)
    {
        return new ResourceGroup($resourceType, new Fluent($options));
    }
}
