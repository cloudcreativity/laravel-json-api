<?php

/**
 * Copyright 2019 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Api\Api;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Support\Fluent;

/**
 * Class ApiGroup
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ApiGroup
{

    /**
     * @var Registrar
     */
    private $router;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var Fluent
     */
    private $options;

    /**
     * ApiGroup constructor.
     *
     * @param Registrar $router
     * @param Api $api
     * @param array $options
     */
    public function __construct(Registrar $router, Api $api, array $options)
    {
        $this->router = $router;
        $this->api = $api;
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
        return new ResourceGroup($resourceType, $this->api->getResolver(), new Fluent($options));
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
            'content-negotiator' => $this->options->get('content-negotiator'),
            'default-authorizer' => $this->options->get('authorizer'),
            'processes' => $this->api->getJobs()->getResource(),
            'prefix' => $this->api->getUrl()->getNamespace(),
            'id' => $this->options->get('id'),
        ];
    }

}
