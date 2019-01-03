<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use DummyApp\Post;
use DummyApp\User;
use Illuminate\Support\Facades\Route;

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

    public function testRead()
    {
        $post = factory(Post::class)->create();
        $uri = route('api:v1:posts.read', ['foo', $post]);

        $this->getJsonApi($uri)->assertFetchedOne([
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'links' => [
                'self' => "http://foo.example.com/api/v1/posts/{$post->getRouteKey()}",
            ],
        ]);
    }

    public function testUpdate()
    {
        $post = factory(Post::class)->create();
        $uri = route('api:v1:posts.update', ['foo', $post]);

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => 'Hello World',
            ],
        ];

        $this->patchJsonApi($uri, [], compact('data'))->assertStatus(200);
    }

    public function testDelete()
    {
        $post = factory(Post::class)->create();
        $uri = route('api:v1:posts.delete', ['foo', $post]);

        $this->deleteJsonApi($uri)->assertStatus(204);
    }

    public function testReadRelated()
    {
        $post = factory(Post::class)->create();
        $uri = route('api:v1:posts.relationships.author', ['foo', $post]);

        $this->getJsonApi($uri)->assertStatus(200);
    }

    public function testReadRelationship()
    {
        $post = factory(Post::class)->create();
        $uri = route('api:v1:posts.relationships.author.read', ['foo', $post]);

        $this->getJsonApi($uri)->assertStatus(200);
    }

    public function testReplaceRelationship()
    {
        $post = factory(Post::class)->create();
        $user = factory(User::class)->create();
        $uri = route('api:v1:posts.relationships.author.replace', ['foo', $post]);

        $data = [
            'type' => 'users',
            'id' => (string) $user->getRouteKey(),
        ];

        $this->patchJsonApi($uri, [], compact('data'))->assertStatus(204);
    }

    /**
     * @return $this|void
     */
    protected function withAppRoutes()
    {
        Route::group([
            'domain' => '{wildcard}.example.com',
        ], function () {
            parent::withAppRoutes();
        });
    }
}
