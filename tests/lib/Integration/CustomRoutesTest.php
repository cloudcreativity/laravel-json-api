<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use DummyApp\Jobs\SharePost;
use DummyApp\Post;
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

        $this->withFluentRoutes()->routes(function (RouteRegistrar $api) {
            $api->resource('posts')->controller()->routes(function (RouteRegistrar $router) {
                $router->post('{record}/share', 'share');
            });
        });
    }

    public function test(): void
    {
        $post = factory(Post::class)->create();
        $uri = url('/api/v1/posts', [$post, 'share']);

        $this->postJsonApi($uri, ['include' => 'author'])
            ->assertFetchedOne($post)
            ->assertIsIncluded('users', $post->author);

        Queue::assertPushed(SharePost::class, function ($job) use ($post) {
            return $post->is($job->post);
        });
    }

    public function testNotFound(): void
    {
        $this->postJsonApi('/api/v1/posts/999/share')->assertErrorStatus([
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

        $this->post($uri, [], ['Accept' => 'application/json'])
            ->assertStatus(406)
            ->assertExactJson($expected);
    }

    public function testValidated(): void
    {
        $post = factory(Post::class)->create();
        $uri = url('/api/v1/posts', [$post, 'share']);

        $expected = [
            'status' => '400',
            'source' => ['parameter' => 'include'],
        ];

        $this->postJsonApi($uri, ['include' => 'foo'])
            ->assertErrorStatus($expected);
    }
}
