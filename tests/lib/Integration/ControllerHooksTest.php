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

use DummyApp\Events\ResourceEvent;
use DummyApp\Post;
use DummyApp\Tag;

class ControllerHooksTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @var array
     */
    private $events;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->events = [];

        app('events')->listen(ResourceEvent::class, function (ResourceEvent $event) {
            $this->events[] = $event->hook;
        });
    }

    /**
     * A search must invoke the `searching` hook.
     */
    public function testSearching()
    {
        $this->doSearch()->assertStatus(200);
        $this->assertHookInvoked('searching');
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

        $this->doCreate($data)->assertStatus(201);

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

        $this->doCreate($data)->assertStatus(422);
        $this->assertNoHooksInvoked();
    }

    /**
     * A successful read must dispatch the `reading` hook.
     */
    public function testRead()
    {
        $post = factory(Post::class)->create();

        $this->doRead($post)->assertStatus(200);
        $this->assertHookInvoked('reading');
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

        $this->doUpdate($data)->assertStatus(200);
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

        $this->doUpdate($data)->assertStatus(422);
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

        $this->doDelete($post)->assertStatus(204);
        $this->assertHooksInvoked('deleting', 'deleted');
    }

    public function testUnsuccessfulDelete()
    {
        $this->doDelete('999')->assertStatus(404);
        $this->assertNoHooksInvoked();
    }

    public function testReadRelated()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelated($post, 'author')->assertStatus(200);
        $this->assertHooksInvoked('reading-relationship', 'reading-author');
    }

    public function testReadRelationship()
    {
        $post = factory(Post::class)->create();

        $this->doReadRelationship($post, 'author')->assertStatus(200);
        $this->assertHooksInvoked('reading-relationship', 'reading-author');
    }

    public function testReplaceRelationship()
    {
        $post = factory(Post::class)->create();

        $this->doReplaceRelationship($post, 'author', null)->assertStatus(204);
        $this->assertHooksInvoked('replacing', 'replacing-author', 'replaced-author', 'replaced');
    }

    public function testAddToRelationship()
    {
        $post = factory(Post::class)->create();
        $tag = ['type' => 'tags', 'id' => (string) factory(Tag::class)->create()->uuid];

        $this->doAddToRelationship($post, 'tags', [$tag])->assertStatus(204);
        $this->assertHooksInvoked('adding', 'adding-tags', 'added-tags', 'added');
    }

    public function testRemoveFromRelationship()
    {
        $post = factory(Post::class)->create();
        /** @var Tag $tag */
        $tag = $post->tags()->create(['name' => 'news']);
        $identifier = ['type' => 'tags', 'id' => $tag->uuid];

        $this->doRemoveFromRelationship($post, 'tags', [$identifier])->assertStatus(204);
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
     * @param $name
     */
    private function assertHookInvoked($name)
    {
        $this->assertSame($this->events, [$name]);
    }

    /**
     * @return void
     */
    private function assertNoHooksInvoked()
    {
        $this->assertEmpty($this->events);
    }
}
