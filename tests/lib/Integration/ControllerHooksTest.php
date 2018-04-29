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
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

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

        $this->assertHooksInvoked('saving', 'creating', 'saved', 'created');
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
            'id' => (string) $post->getKey(),
            'attributes' => [
                'title' => 'My First Post',
            ],
        ];

        $this->doUpdate($data)->assertStatus(200);
        $this->assertHooksInvoked('saving', 'updating', 'saved', 'updated');
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

    /**
     * @param mixed ...$names
     */
    private function assertHooksInvoked(...$names)
    {
        foreach ($names as $name) {
            $this->assertHookInvoked($name);
        }
    }

    /**
     * @param $name
     */
    private function assertHookInvoked($name)
    {
        Event::assertDispatched(ResourceEvent::class, function ($event) use ($name) {
            return $name === $event->hook;
        });
    }

    /**
     * @param $name
     */
    private function assertHookNotInvoked($name)
    {
        Event::assertNotDispatched(ResourceEvent::class, function ($event) use ($name) {
            return $name === $event->hook;
        });
    }

    /**
     * @return void
     */
    private function assertNoHooksInvoked()
    {
        Event::assertNotDispatched(ResourceEvent::class);
    }
}
