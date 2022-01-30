<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Auth;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use Illuminate\Support\Facades\Route;

class Issue284Test extends TestCase
{

    /**
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * Test authorization exception *before* JSON API middleware.
     */
    public function test()
    {
        Route::group([
            'namespace' => '\\DummyApp\\Http\\Controllers',
            'middleware' => 'auth'
        ], function () {
            JsonApi::register('v1', [], function (RouteRegistrar $api) {
                $api->resource('posts');
            });
        });

        $response = $this
            ->jsonApi()
            ->get('/api/v1/posts');

        $response->assertErrorStatus([
            'status' => '401',
            'title' => 'Unauthenticated',
        ]);
    }

    public function testFluent()
    {
        Route::group([
            'namespace' => '\\DummyApp\\Http\\Controllers',
            'middleware' => 'auth'
        ], function () {
            JsonApi::register('v1')->routes(function (RouteRegistrar $api) {
                $api->resource('posts');
            });
        });

        $response = $this
            ->jsonApi()
            ->get('/api/v1/posts');

        $response->assertErrorStatus([
            'status' => '401',
            'title' => 'Unauthenticated',
        ]);
    }
}
