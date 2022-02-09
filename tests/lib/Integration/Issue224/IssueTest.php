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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Issue224;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\JsonApi\Users\Adapter;
use DummyApp\User;
use Illuminate\Support\Facades\Route;

class IssueTest extends TestCase
{

    /**
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind('DummyApp\\JsonApi\\EndUsers\\Adapter', Adapter::class);
        $this->app->bind('DummyApp\\JsonApi\\EndUsers\\Schema', Schema::class);

        Route::group([
            'namespace' => 'DummyApp\\Http\\Controllers',
        ], function () {
            JsonApi::register('v1', [], function (RouteRegistrar $api) {
                $api->resource('endUsers');
            });
        });

        $this->refreshRoutes();

        config()->set('json-api-v1.resources', [
            'endUsers' => User::class,
        ]);
    }

    public function test()
    {
        $user = factory(User::class)->create();

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/endUsers', $user));

        $response->assertFetchedOne([
            'type' => 'endUsers',
            'id' => (string) $user->getRouteKey(),
            'attributes' => [
                'name' => $user->name,
            ],
        ]);
    }
}
