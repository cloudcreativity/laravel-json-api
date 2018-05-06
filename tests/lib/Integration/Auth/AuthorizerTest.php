<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Auth;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Post;
use Illuminate\Support\Facades\Route;

class AuthorizerTest extends TestCase
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
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        Route::group([
            'namespace' => 'DummyApp\\Http\\Controllers',
        ], function () {
            JsonApi::register('default', ['middleware' => "json-api.auth:generic"], function (ApiGroup $api) {
                $api->resource('posts');
            });
        });
    }

    public function testIndexUnauthenticated()
    {
        $this->doSearch()->assertStatus(401)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);
    }

    public function testIndexAllowed()
    {
        $this->actingAsUser()
            ->doSearch()
            ->assertStatus(200);
    }

    public function testCreateUnauthenticated()
    {
        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => 'Hello World',
                'content' => '...',
                'slug' => 'hello-world',
            ],
        ];

        $this->doCreate($data)->assertStatus(401)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);

        return $data;
    }

    /**
     * @param array $data
     * @depends testCreateUnauthenticated
     */
    public function testCreateUnauthorized(array $data)
    {
        $this->actingAsUser()->doCreate($data)->assertStatus(403)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Unauthorized',
                    'status' => '403',
                ],
            ],
        ]);
    }

    /**
     * @param array $data
     * @depends testCreateUnauthenticated
     */
    public function testCreateAllowed(array $data)
    {
        $this->actingAsUser('author')
            ->doCreate($data)
            ->assertStatus(201);
    }

    public function testReadUnauthenticated()
    {
        $post = factory(Post::class)->states('published')->create();

        $this->doRead($post)->assertStatus(401)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);
    }

    public function testReadUnauthorized()
    {
        $post = factory(Post::class)->create();

        $this->actingAsUser()->doRead($post)->assertStatus(403)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Unauthorized',
                    'status' => '403',
                ],
            ],
        ]);
    }

    public function testReadAllowed()
    {
        $post = factory(Post::class)->create();

        $this->actingAs($post->author)
            ->doRead($post)
            ->assertStatus(200);
    }

    public function testUpdateUnauthenticated()
    {
        $post = factory(Post::class)->states('published')->create();
        $data = [
            'type' => 'posts',
            'id' => (string) $post->getKey(),
            'attributes' => [
                'title' => 'Hello World'
            ],
        ];

        $this->doUpdate($data)->assertStatus(401)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);
    }

    public function testUpdateUnauthorized()
    {
        $post = factory(Post::class)->create();
        $data = [
            'type' => 'posts',
            'id' => (string) $post->getKey(),
            'attributes' => [
                'title' => 'Hello World'
            ],
        ];

        $this->actingAsUser()->doUpdate($data)->assertStatus(403)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Unauthorized',
                    'status' => '403',
                ],
            ],
        ]);
    }

    public function testUpdateAllowed()
    {
        $post = factory(Post::class)->create();
        $data = [
            'type' => 'posts',
            'id' => (string) $post->getKey(),
            'attributes' => [
                'title' => 'Hello World'
            ],
        ];

        $this->actingAs($post->author)
            ->doUpdate($data)
            ->assertStatus(200);
    }


    public function testDeleteUnauthenticated()
    {
        $post = factory(Post::class)->states('published')->create();

        $this->doDelete($post)->assertStatus(401)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Unauthenticated',
                    'status' => '401',
                ],
            ],
        ]);

        $this->assertDatabaseHas('posts', ['id' => $post->getKey()]);
    }

    public function testDeleteUnauthorized()
    {
        $post = factory(Post::class)->create();

        $this->actingAsUser()->doDelete($post)->assertStatus(403)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Unauthorized',
                    'status' => '403',
                ],
            ],
        ]);

        $this->assertDatabaseHas('posts', ['id' => $post->getKey()]);
    }

    public function testDeleteAllowed()
    {
        $post = factory(Post::class)->create();

        $this->actingAs($post->author)
            ->doDelete($post)
            ->assertStatus(204);

        $this->assertDatabaseMissing('posts', ['id' => $post->getKey()]);
    }

}
