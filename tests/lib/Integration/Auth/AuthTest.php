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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Auth;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class AuthTest extends TestCase
{

    /**
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * Test that we can use Laravel's auth middleware to protect the entire API.
     */
    public function testApiAuthDisallowed()
    {
        $this->withApiMiddleware()->doSearch()->assertStatus(401)->assertJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);
    }

    /**
     * Test that an authenticated user can access resource in protected API.
     */
    public function testApiAuthAllowed()
    {
        $this->withApiMiddleware()
            ->actingAsUser()
            ->doSearch()
            ->assertSuccessful();
    }

    /**
     * @return array
     */
    public function resourceAuthProvider()
    {
        return [
            [false, 'posts', 200],
            [true, 'posts', 200],
            [false, 'comments', 401],
            [true, 'comments', 200],
        ];
    }

    /**
     * @param $authenticated
     * @param $resourceType
     * @param $expected
     * @dataProvider resourceAuthProvider
     */
    public function testResourceAuth($authenticated, $resourceType, $expected)
    {
        if ($authenticated) {
            $this->actingAsUser();
        }

        $this->resourceType = $resourceType;
        $response = $this->withResourceMiddleware()->doSearch()->assertStatus($expected);

        if (200 !== $expected) {
            $response->assertJson([
                'errors' => [
                    [
                        'title' => 'Unauthenticated',
                        'status' => '401',
                    ],
                ],
            ]);
        }
    }

    /**
     * Set up authentication on the whole API.
     *
     * @return $this
     */
    private function withApiMiddleware()
    {
        Auth::routes();

        Route::group([
            'namespace' => 'DummyApp\\Http\\Controllers',
        ], function () {
            JsonApi::register('v1', [
                'middleware' => 'auth',
            ], function (ApiGroup $api) {
                $api->resource('posts');
            });
        });

        $this->refreshRoutes();

        return $this;
    }

    /**
     * Set up authentication on one resource.
     *
     * @return $this
     */
    private function withResourceMiddleware()
    {
        Auth::routes();

        Route::group([
            'namespace' => 'DummyApp\\Http\\Controllers',
        ], function () {
            JsonApi::register('v1', [], function (ApiGroup $api) {
                $api->resource('posts');
                $api->resource('comments', [
                    'middleware' => 'auth',
                ]);
            });
        });

        $this->refreshRoutes();

        return $this;
    }

}
