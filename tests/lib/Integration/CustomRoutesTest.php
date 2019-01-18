<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use DummyApp\Jobs\SharePost;
use DummyApp\Post;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Queue;

class CustomRoutesTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

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

        Queue::fake();

        $this->withRoutes(function (ApiGroup $api, Router $router) {
            $router->get('/posts/{record}/share', 'PostsController@share')
                ->middleware('json-api.content')
                ->defaults('resource_type', 'posts');

            $api->resource('posts');
        });
    }

    public function test(): void
    {
        $post = factory(Post::class)->create();
        $uri = url('/api/v1/posts', [$post, 'share']);

        $this->getJsonApi($uri, ['include' => 'author'])
            ->assertFetchedOne($post)
            ->assertIsIncluded('users', $post->author);

        Queue::assertPushed(SharePost::class, function ($job) use ($post) {
            return $post->is($job->post);
        });
    }

    public function testNotFound(): void
    {
        $this->getJsonApi('/api/v1/posts/999/share')->assertErrorStatus([
            'status' => '404',
            'title' => 'Not Found',
        ]);
    }

    public function testContentIsNegotiated(): void
    {
        $expected = ['message' =>
            "The requested resource is capable of generating only content not acceptable "
            . "according to the Accept headers sent in the request."
        ];

        $post = factory(Post::class)->create();
        $uri = url('/api/v1/posts', [$post, 'share']);

        $this->get($uri, ['Accept' => 'application/json'])
            ->assertStatus(406)
            ->assertExactJson($expected);
    }
}
