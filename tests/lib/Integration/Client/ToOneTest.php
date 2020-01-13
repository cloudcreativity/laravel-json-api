<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Encoder\Parameters\EncodingParameters;
use DummyApp\Post;

class ToOneTest extends TestCase
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

    public function testRelated()
    {
        $expected = $this->willSeeResource($this->post->author);
        $actual = $this->client->readRecordRelated($this->post, 'author');

        $this->assertSame($expected, $actual);
        $this->assertRequested('GET', "/posts/{$this->post->getRouteKey()}/author");
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testRelatedWithParameters()
    {
        $this->willSeeResource($this->post->author);

        $this->client->readRecordRelated($this->post, 'author', [
            'include' => 'posts',
        ]);

        $this->assertQueryParameters([
            'include' => 'posts',
        ]);
    }

    public function testRelatedWithEncodingParameters()
    {
        $parameters = new EncodingParameters(
            ['posts'],
            ['author' => ['first-name', 'surname']]
        );

        $this->willSeeResource($this->post->author);
        $this->client->readRecordRelated($this->post, 'author', $parameters);

        $this->assertQueryParameters([
            'include' => 'posts',
            'fields[author]' => 'first-name,surname',
        ]);
    }

    public function testRelationship()
    {
        $expected = $this->willSeeIdentifiers($this->post->author);
        $actual = $this->client->readRecordRelationship($this->post, 'author');

        $this->assertSame($expected, $actual);
        $this->assertRequested('GET', "/posts/{$this->post->getRouteKey()}/relationships/author");
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testRelationshipWithParameters()
    {
        $this->willSeeIdentifiers($this->post->author);

        $this->client->readRecordRelationship($this->post, 'author', [
            'include' => 'posts',
            'fields' => ['author' => 'first-name,surname'],
        ]);

        $this->assertQueryParameters([
            'include' => 'posts',
            'fields[author]' => 'first-name,surname',
        ]);
    }

    public function testRelationshipWithEncodingParameters()
    {
        $parameters = new EncodingParameters(
            ['posts'],
            ['author' => ['first-name', 'surname']]
        );

        $this->willSeeIdentifiers($this->post->author);
        $this->client->readRecordRelationship($this->post, 'author', $parameters);

        $this->assertQueryParameters([
            'include' => 'posts',
            'fields[author]' => 'first-name,surname',
        ]);
    }

    public function testReplace()
    {
        $expected = $this->willSeeIdentifiers($this->post->author);
        $actual = $this->client->replaceRecordRelationship($this->post, $this->post->author, 'author');

        $data = [
            'type' => 'users',
            'id' => (string) $this->post->author_id,
        ];

        $this->assertSame($expected, $actual);
        $this->assertRequested('PATCH', "/posts/{$this->post->getRouteKey()}/relationships/author");
        $this->assertSentDocument(compact('data'));
    }
}
