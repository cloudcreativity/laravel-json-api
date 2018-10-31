<?php

/**
 * Copyright 2018 Cloud Creativity Limited
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
class ResourceRegistrar
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
     * ResourceRegistrar constructor.
     *
     * @param Registrar $router
     * @param Repository $apiRepository
     */
    public function __construct(Registrar $router, Repository $apiRepository)
    {
        $this->router = $router;
        $this->apiRepository = $apiRepository;
    }

    /**
     * @param $apiName
     * @param array $options
     * @param Closure $routes
     * @return void
     */
    public function api($apiName, array $options, Closure $routes)
    {
        $api = $this->apiRepository->createApi($apiName);
        $url = $api->getUrl();

        $this->router->group([
            'middleware' => ["json-api:{$apiName}", "json-api.bindings", "json-api.content"],
            'as' => $url->getName(),
            'prefix' => $url->getNamespace(),
        ], function () use ($api, $options, $routes) {
            $group = new ApiGroup($this->router, $api, $options);

            $this->router->group($options, function () use ($api, $group, $routes) {
                $routes($group, $this->router);

                $this->apiRepository
                    ->createProviders($api->getName())
                    ->mountAll($group, $this->router);
            });
        });
    }

}
