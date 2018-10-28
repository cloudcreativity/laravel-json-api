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

        $this->doRead($post)->assertRead([
            'type' => 'posts',
            'id' => (string) $post->getKey(),
            'links' => [
                'self' => "http://foo.example.com/api/v1/posts/{$post->getKey()}",
            ],
        ]);
    }

    public function testUpdate()
    {
        $post = factory(Post::class)->create();

        $this->doUpdate([
            'type' => 'posts',
            'id' => (string) $post->getKey(),
            'attributes' => [
                'title' => 'Hello World',
            ],
        ])->assertStatus(200);
    }

    public function testDelete()
    {
        $post = factory(Post::class)->create();

        $this->doDelete($post)->assertStatus(204);
    }

    public function testReadRelated()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelated($post, 'author')->assertStatus(200);
    }

    public function testReadRelationship()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelationship($post, 'author')->assertStatus(200);
    }

    public function testReplaceRelationship()
    {
        $post = factory(Post::class)->create();
        $user = factory(User::class)->create();

        $this->doReplaceRelationship($post, 'author', [
            'type' => 'users',
            'id' => (string) $user->getKey()
        ])->assertStatus(204);
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

    /**
     * @param array $params
     * @return array
     */
    protected function addDefaultRouteParams(array $params)
    {
        $params['wildcard'] = 'foo';

        return $params;
    }
}
