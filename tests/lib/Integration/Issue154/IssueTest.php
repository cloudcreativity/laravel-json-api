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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Issue154;

use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Post;

class IssueTest extends TestCase
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

        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts', ['controller' => true]);
        });
    }

    /**
     * @return array
     */
    public static function createProvider()
    {
        return [
            ['saving', ['creating', 'saved', 'created']],
            ['creating', ['saved', 'created']],
            ['created', ['saved']],
            ['saved', []],
        ];
    }

    /**
     * @param $hook
     * @param array $unexpected
     * @dataProvider createProvider
     */
    public function testCreate($hook, array $unexpected)
    {
        $post = factory(Post::class)->make();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'title' => $post->title,
                'slug' => $post->slug,
                'content' => $post->content,
            ],
            'relationships' => [
                'author' => [
                    'data' => [
                        'type' => 'users',
                        'id' => (string) $post->author_id,
                    ],
                ],
            ],
        ];

        $this->withResponse($hook, $unexpected);

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/posts');

        $response->assertStatus(202);
    }

    /**
     * @return array
     */
    public static function updateProvider()
    {
        return [
            ['saving', ['updating', 'saved', 'updated']],
            ['updating', ['saved', 'updated']],
            ['updated', ['saved']],
            ['saved', []],
        ];
    }

    /**
     * @param $hook
     * @param array $unexpected
     * @dataProvider updateProvider
     */
    public function testUpdate($hook, array $unexpected)
    {
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getKey(),
            'attributes' => [
                'title' => 'My First Post',
            ],
        ];

        $this->withResponse($hook, $unexpected);

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertStatus(202);
    }

    /**
     * @return array
     */
    public static function deleteProvider()
    {
        return [
            ['deleting', ['deleted']],
            ['deleted', []],
        ];
    }

    /**
     * @param $hook
     * @param array $unexpected
     * @dataProvider deleteProvider
     */
    public function testDelete($hook, array $unexpected)
    {
        $post = factory(Post::class)->create();

        $this->withResponse($hook, $unexpected);

        $response = $this
            ->jsonApi()
            ->delete(url('/api/v1/posts', $post));

        $response->assertStatus(202);
    }

    /**
     * @param $hook
     * @param array $unexpected
     *      hooks that must not be invoked.
     * @return $this
     */
    private function withResponse($hook, array $unexpected = [])
    {
        $this->app->instance('DummyApp\Http\Controllers\PostsController', $controller = new Controller());
        $controller->responses[$hook] = response('', 202);
        $controller->unexpected = $unexpected;

        return $this;
    }
}
