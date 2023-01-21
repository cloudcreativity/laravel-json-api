<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Routing;

use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Post;
use DummyApp\User;

/**
 * Class SubDomainTest
 *
 * Tests routing when there is a route parameter before the JSON API route
 * parameters, in this case a wildcard domain. We need to test that this
 * does not affect the JSON API controller from obtaining route parameters.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class SubDomainTest extends TestCase
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
    protected function setUp(): void
    {
        parent::setUp();

        $this->withFluentRoutes()->domain('{wildcard}.example.com')->routes(function (RouteRegistrar $api) {
            $api->resource('posts')->relationships(function ($relations) {
               $relations->hasOne('author');
            });
        });
    }

    public function testRead()
    {
        $post = factory(Post::class)->create();
        $uri = "http://foo.example.com/api/v1/posts/{$post->getRouteKey()}";

        $response = $this->jsonApi()->get($uri);

        $response->assertFetchedOne([
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'links' => [
                'self' => $uri,
            ],
        ]);
    }

    public function testUpdate()
    {
        $post = factory(Post::class)->create();
        $uri = "http://foo.example.com/api/v1/posts/{$post->getRouteKey()}";

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => 'Hello World',
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch($uri);

        $response->assertStatus(200);
    }

    public function testDelete()
    {
        $post = factory(Post::class)->create();
        $uri = "http://foo.example.com/api/v1/posts/{$post->getRouteKey()}";

        $response = $this
            ->jsonApi()
            ->delete($uri);

        $response->assertStatus(204);
    }

    public function testReadRelated()
    {
        $post = factory(Post::class)->create();
        $uri = "http://foo.example.com/api/v1/posts/{$post->getRouteKey()}/author";

        $response = $this->jsonApi()->get($uri);

        $response->assertStatus(200);
    }

    public function testReadRelationship()
    {
        $post = factory(Post::class)->create();
        $uri = "http://foo.example.com/api/v1/posts/{$post->getRouteKey()}/relationships/author";

        $response = $this->jsonApi()->get($uri);

        $response->assertStatus(200);
    }

    public function testReplaceRelationship()
    {
        $post = factory(Post::class)->create();
        $user = factory(User::class)->create();
        $uri = "http://foo.example.com/api/v1/posts/{$post->getRouteKey()}/relationships/author";

        $data = [
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch($uri);

        $response->assertStatus(204);
    }

}
