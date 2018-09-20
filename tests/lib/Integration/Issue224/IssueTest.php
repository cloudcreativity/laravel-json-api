<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Issue224;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
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
     * @var string
     */
    protected $resourceType = 'endUsers';

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->app->bind('DummyApp\\JsonApi\\EndUsers\\Adapter', Adapter::class);
        $this->app->bind('DummyApp\\JsonApi\\EndUsers\\Schema', Schema::class);

        Route::group([
            'namespace' => 'DummyApp\\Http\\Controllers',
        ], function () {
            JsonApi::register('v1', [], function (ApiGroup $api) {
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

        $this->getJsonApi("/api/v1/endUsers/{$user->getRouteKey()}")->assertRead([
            'type' => 'endUsers',
            'id' => $user->getRouteKey(),
            'attributes' => [
                'name' => $user->name,
            ],
        ]);
    }
}
