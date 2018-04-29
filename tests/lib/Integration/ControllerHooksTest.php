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

use DummyApp\Events\ResourceEvent;
use DummyApp\Post;
use Illuminate\Support\Facades\Event;

class ControllerHooksTest extends TestCase
{

    protected $resourceType = 'posts';

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        Event::fake([ResourceEvent::class]);
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

        $id = $this->doCreate($data)->assertCreatedWithId();

        Event::assertDispatched(ResourceEvent::class, function ($event) use ($data) {
            return 'saving' === $event->hook && $data === $event->resource->toArray();
        });

        Event::assertDispatched(ResourceEvent::class, function ($event) use ($data) {
            return 'creating' === $event->hook && $data === $event->resource->toArray();
        });

        Event::assertDispatched(ResourceEvent::class, function ($event) use ($id, $data) {
            return 'saved' === $event->hook &&
                $id == $event->record->id &&
                $data === $event->resource->toArray();
        });

        Event::assertDispatched(ResourceEvent::class, function ($event) use ($id, $data) {
            return 'created' === $event->hook &&
                $id == $event->record->id &&
                $data === $event->resource->toArray();
        });
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

        Event::assertNotDispatched(ResourceEvent::class);
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
            'id' => (string) $post->getKey(),
            'attributes' => [
                'title' => 'My First Post',
            ],
        ];

        $this->doUpdate($data)->assertStatus(200);

        Event::assertDispatched(ResourceEvent::class, function ($event) use ($post, $data) {
            return 'saving' === $event->hook &&
                $post->is($event->record) &&
                $data === $event->resource->toArray();
        });

        Event::assertDispatched(ResourceEvent::class, function ($event) use ($post, $data) {
            return 'updating' === $event->hook &&
                $post->is($event->record) &&
                $data === $event->resource->toArray();
        });

        Event::assertDispatched(ResourceEvent::class, function ($event) use ($post, $data) {
            return 'saved' === $event->hook &&
                $post->is($event->record) &&
                $data === $event->resource->toArray();
        });

        Event::assertDispatched(ResourceEvent::class, function ($event) use ($post, $data) {
            return 'updated' === $event->hook &&
                $post->is($event->record) &&
                $data === $event->resource->toArray();
        });
    }

    public function testUnsuccessfulUpdate()
    {
        $post = factory(Post::class)->create();

        $data = [
            'type' => 'posts',
            'id' => (string) $post->getKey(),
            'attributes' => ['title' => null],
        ];

        $this->doUpdate($data)->assertStatus(422);

        Event::assertNotDispatched(ResourceEvent::class);
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

        Event::assertDispatched(ResourceEvent::class, function ($event) use ($post) {
            return 'deleting' === $event->hook &&
                $post->is($event->record);
        });

        Event::assertDispatched(ResourceEvent::class, function ($event) use ($post) {
            return 'deleted' === $event->hook &&
                $post->id == $event->record;
        });
    }

    public function testUnsuccessfulDelete()
    {
        $this->doDelete('999')->assertStatus(404);

        Event::assertNotDispatched(ResourceEvent::class);
    }
}
