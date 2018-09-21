<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Resolver;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\JsonApi\Posts\Adapter;
use DummyApp\Post;
use Illuminate\Support\Facades\Route;

class ResolverTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'foobars';

    /**
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        config()->set('json-api-v1.resolver', CreateCustomResolver::class);
        config()->set('json-api-v1.resources', [
            'foobars' => Post::class,
        ]);

        $this->app->bind('adapters:foobars', Adapter::class);
        $this->app->bind('schemas:foobars', Schema::class);

        Route::group([
            'namespace' => 'DummyApp\\Http\\Controllers',
        ], function () {
            JsonApi::register('v1', [], function (ApiGroup $api) {
                $api->resource('foobars');
            });
        });
    }

    public function test()
    {
        $post = factory(Post::class)->create();

        $this->doRead($post)->assertRead([
            'type' => 'foobars',
            'id' => $post->getRouteKey(),
            'attributes' => [
                'title' => $post->title,
            ],
        ]);
    }
}
