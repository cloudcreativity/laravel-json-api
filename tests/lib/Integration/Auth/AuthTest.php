<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Auth;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
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
        $this->withApiMiddleware()->doSearch()->assertStatus(401)->assertExactJson([
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
            $response->assertExactJson([
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
        Route::group([
            'namespace' => 'DummyApp\\Http\\Controllers',
        ], function () {
            JsonApi::register('default', [
                'middleware' => 'auth',
            ], function (ApiGroup $api) {
                $api->resource('posts');
            });
        });

        return $this;
    }

    /**
     * Set up authentication on one resource.
     *
     * @return $this
     */
    private function withResourceMiddleware()
    {
        Route::group([
            'namespace' => 'DummyApp\\Http\\Controllers',
        ], function () {
            JsonApi::register('default', [], function (ApiGroup $api) {
                $api->resource('posts');
                $api->resource('comments', [
                    'middleware' => 'auth',
                ]);
            });
        });

        return $this;
    }

}
