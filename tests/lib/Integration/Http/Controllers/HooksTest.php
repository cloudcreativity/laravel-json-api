<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Post;
use DummyApp\Tag;

class HooksTest extends TestCase
{

    /**
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * @var array
     */
    private $events;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->instance('DummyApp\Http\Controllers\PostsController', new TestController());

        $this->withRoutes(function (RouteRegistrar $api) {
            $api->resource('posts', [
                'controller' => true,
                'has-one' => 'author',
                'has-many' => 'tags',
            ]);
        });

        $this->events = [];

        app('events')->listen(TestEvent::class, function ($event) {
            $this->events[] = $event->hook;
        });
    }

    /**
     * A search must invoke the `searching` hook.
     */
    public function testSearching()
    {
        $response = $this
            ->jsonApi()
            ->get('/api/v1/posts');

        $response->assertStatus(200);
        $this->assertHooksInvoked('searching', 'searched');
    }

    /**
     * A successful create must invoke the following hooks:
     *
     * - saving
     * - creating
     * - saved
     * - created
     */
    public function testCreate()
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

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/posts');

        $response->assertStatus(201);

        $this->assertHooksInvoked('saving', 'creating', 'created', 'saved');
    }

    public function testUnsuccessfulCreate()
    {
        $post = factory(Post::class)->make();

        $data = [
            'type' => 'posts',
            'attributes' => [
                'content' => $post->content,
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->post('/api/v1/posts');

        $response->assertStatus(422);
        $this->assertNoHooksInvoked();
    }

    /**
     * A successful read must dispatch the `reading` hook.
     */
    public function testRead()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/posts', $post));

        $response->assertStatus(200);
        $this->assertHooksInvoked('reading', 'did-read');
    }

    /**
     * A successful update must invoke the following hooks:
     *
     * - saving
     * - updating
     * - saved
     * - updated
     */
    public function testUpdate()
    {
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => [
                'title' => 'My First Post',
            ],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertStatus(200);
        $this->assertHooksInvoked('saving', 'updating', 'updated', 'saved');
    }

    public function testUnsuccessfulUpdate()
    {
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'attributes' => ['title' => null],
        ];

        $response = $this
            ->jsonApi()
            ->withData($data)
            ->patch(url('/api/v1/posts', $post));

        $response->assertStatus(422);
        $this->assertNoHooksInvoked();
    }

    /**
     * A successful delete must invoke the following hooks:
     *
     * - deleting
     * - deleted
     */
    public function testDelete()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi()
            ->delete(url('/api/v1/posts', $post));

        $response->assertStatus(204);
        $this->assertHooksInvoked('deleting', 'deleted');
    }

    public function testUnsuccessfulDelete()
    {
        $response = $this
            ->jsonApi()
            ->delete('/api/v1/posts/999');

        $response->assertStatus(404);
        $this->assertNoHooksInvoked();
    }

    public function testReadRelated()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/posts', [$post, 'author']));

        $response->assertStatus(200);

        $this->assertHooksInvoked(
            'reading-relationship',
            'reading-author',
            'did-read-author',
            'did-read-relationship'
        );
    }

    public function testReadRelationship()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/posts', [$post, 'relationships', 'author']));

        $response->assertStatus(200);

        $this->assertHooksInvoked(
            'reading-relationship',
            'reading-author',
            'did-read-author',
            'did-read-relationship'
        );
    }

    public function testReplaceRelationship()
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi()
            ->withData(null)
            ->patch(url('/api/v1/posts', [$post, 'relationships', 'author']));

        $response->assertStatus(204);
        $this->assertHooksInvoked('replacing', 'replacing-author', 'replaced-author', 'replaced');
    }

    public function testAddToRelationship()
    {
        $post = factory(Post::class)->create();
        $tag = ['type' => 'tags', 'id' => (string) factory(Tag::class)->create()->uuid];

        $response = $this
            ->jsonApi()
            ->withData([$tag])
            ->post(url('/api/v1/posts', [$post, 'relationships', 'tags']));

        $response->assertStatus(204);
        $this->assertHooksInvoked('adding', 'adding-tags', 'added-tags', 'added');
    }

    public function testRemoveFromRelationship()
    {
        $post = factory(Post::class)->create();
        /** @var Tag $tag */
        $tag = $post->tags()->create(['name' => 'news']);
        $identifier = ['type' => 'tags', 'id' => $tag->uuid];

        $response = $this
            ->jsonApi()
            ->withData([$identifier])
            ->delete(url('/api/v1/posts', [$post, 'relationships', 'tags']));

        $response->assertStatus(204);
        $this->assertHooksInvoked('removing', 'removing-tags', 'removed-tags', 'removed');
    }

    /**
     * @param mixed ...$names
     */
    private function assertHooksInvoked(...$names)
    {
        $this->assertSame($this->events, $names);
    }

    /**
     * @return void
     */
    private function assertNoHooksInvoked()
    {
        $this->assertEmpty($this->events);
    }
}
