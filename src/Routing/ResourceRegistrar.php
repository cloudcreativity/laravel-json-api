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

use Closure;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use Illuminate\Contracts\Routing\Registrar;

/**
 * Class ResourceRegistrar
 *
 * @package CloudCreativity\LaravelJsonApi
 */
final class ResourceRegistrar
{

    const KEYWORD_RELATIONSHIPS = 'relationships';
    const KEYWORD_PROCESSES = 'queue-jobs';
    const PARAM_RESOURCE_TYPE = 'resource_type';
    const PARAM_RESOURCE_ID = 'record';
    const PARAM_RELATIONSHIP_NAME = 'relationship_name';
    const PARAM_RELATIONSHIP_INVERSE_TYPE = 'relationship_inverse_type';
    const PARAM_PROCESS_TYPE = 'process_type';
    const PARAM_PROCESS_ID = 'process';

    /**
     * @var Registrar
     */
    protected $router;

    /**
     * @var Repository
     */
    protected $apiRepository;

    /**
     * @var array
     */
    private $attributes;

    /**
     * ResourceRegistrar constructor.
     *
     * @param Registrar $router
     * @param Repository $apiRepository
     */
    public function __construct(Registrar $router, Repository $apiRepository)
    {
        $this->router = $router;
        $this->apiRepository = $apiRepository;
        $this->attributes = [];
    }

    /**
     * @param string $apiName
     * @param array|Closure $options
     * @param Closure|null $routes
     * @return ApiRegistration
     */
    public function api(string $apiName, $options = [], Closure $routes = null): ApiRegistration
    {
        if ($options instanceof Closure) {
            $routes = $options;
            $options = [];
        }

        $api = new ApiRegistration(
            $this->router,
            $this->apiRepository->createApi($apiName),
            $options
        );

        if ($routes instanceof Closure) {
            $api->group($routes);
        }

        return $api;
    }

}
