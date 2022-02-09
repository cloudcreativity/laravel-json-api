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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Auth;

use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Post;

class AuthorizerTest extends TestCase
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

        $this->withFluentRoutes()
            ->authorizer('generic')
            ->routes(function (RouteRegistrar $api) {
                $api->resource('posts');
            });
    }

    public function testIndexUnauthenticated()
    {
        $response = $this->jsonApi()->get('/api/v1/posts');

        $response->assertStatus(401)->assertJson([
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
        $response = $this
            ->actingAsUser()
            ->jsonApi()
            ->get('/api/v1/posts');

        $response->assertStatus(200);
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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/posts');

        $response->assertStatus(401)->assertJson([
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
        $response = $this
            ->actingAsUser()
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/posts');

        $response->assertStatus(403)->assertJson([
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
        $response = $this
            ->actingAsUser('author')
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/posts');

        $response->assertStatus(201);
    }

    public function testReadUnauthenticated()
    {
        $post = factory(Post::class)->states('published')->create();

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/posts', $post));

        $response->assertStatus(401)->assertJson([
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

        $response = $this
            ->actingAsUser()
            ->jsonApi()
            ->get(url('/api/v1/posts', $post));

        $response->assertStatus(403)->assertJson([
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

        $response = $this
            ->actingAs($post->author, 'api')
            ->jsonApi()
            ->get(url('/api/v1/posts', $post));

        $response->assertStatus(200);
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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertStatus(401)->assertJson([
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

        $response = $this
            ->actingAsUser()
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertStatus(403)->assertJson([
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

        $response = $this
            ->actingAs($post->author, 'api')
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertStatus(200);
    }


    public function testDeleteUnauthenticated()
    {
        $post = factory(Post::class)->states('published')->create();

        $response = $this
            ->jsonApi()
            ->delete(url('/api/v1/posts', $post));

        $response->assertStatus(401)->assertJson([
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

        $response = $this
            ->actingAsUser()
            ->jsonApi()
            ->delete(url('/api/v1/posts', $post));

        $response->assertStatus(403)->assertJson([
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

        $response = $this
            ->actingAs($post->author, 'api')
            ->jsonApi()
            ->delete(url('/api/v1/posts', $post));

        $response->assertStatus(204);

        $this->assertDatabaseMissing('posts', ['id' => $post->getKey()]);
    }

}
