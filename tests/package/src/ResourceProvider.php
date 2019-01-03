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

namespace DummyPackage;

use CloudCreativity\LaravelJsonApi\Api\ResourceProvider as BaseResourceProvider;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use Illuminate\Contracts\Routing\Registrar;

class ResourceProvider extends BaseResourceProvider
{

    /**
     * @var array
     */
    protected $resources = [
        'blogs' => Blog::class,
    ];

    /**
     * @inheritDoc
     */
    public function mount(ApiGroup $api, Registrar $router)
    {
        $api->resource('blogs', [
            'controller' => '\\' . Http\Controllers\BlogsController::class,
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function getRootNamespace()
    {
        return __NAMESPACE__ . '\\Resources';
    }

}
