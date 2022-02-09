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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use DummyApp\Post;
use DummyApp\Tag;

class ToManyTest extends TestCase
{

    /**
     * @var Post
     */
    private $post;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->post = factory(Post::class)->create();
    }

    public function testAddTo()
    {
        $tags = factory(Tag::class, 2)->create();

        $expected = $this->willSeeIdentifiers($tags);
        $actual = $this->client->addToRecordRelationship($this->post, $tags, 'tags');

        $data = [
            ['type' => 'tags', 'id' => $tags->first()->getRouteKey()],
            ['type' => 'tags', 'id' => $tags->last()->getRouteKey()],
        ];

        $this->assertSame($expected, $actual);
        $this->assertRequested('POST', "/posts/{$this->post->getRouteKey()}/relationships/tags");
        $this->assertSentDocument(compact('data'));
    }

    public function testRemoveFrom()
    {
        $tags = factory(Tag::class, 2)->create();

        $expected = $this->willSeeIdentifiers($tags);
        $actual = $this->client->removeFromRecordRelationship($this->post, $tags, 'tags');

        $data = [
            ['type' => 'tags', 'id' => $tags->first()->getRouteKey()],
            ['type' => 'tags', 'id' => $tags->last()->getRouteKey()],
        ];

        $this->assertSame($expected, $actual);
        $this->assertRequested('DELETE', "/posts/{$this->post->getRouteKey()}/relationships/tags");
        $this->assertSentDocument(compact('data'));
    }
}
