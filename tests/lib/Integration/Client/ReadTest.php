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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use CloudCreativity\LaravelJsonApi\Exceptions\ClientException;
use DummyApp\Post;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

class ReadTest extends TestCase
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

    public function test()
    {
        $expected = $this->willSeeResource($this->post);
        $response = $this->client->read('posts', $this->post->getRouteKey());

        $this->assertSame($expected, $response);
        $this->assertRequested('GET', "/posts/{$this->post->getRouteKey()}");
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testWithObject()
    {
        $expected = $this->willSeeResource($this->post);
        $response = $this->client->readRecord($this->post);

        $this->assertSame($expected, $response);
        $this->assertRequested('GET', "/posts/{$this->post->getRouteKey()}");
    }

    public function testWithParameters()
    {
        $this->willSeeResource($this->post);

        $this->client->read('posts', '1', [
            'include' => 'author,site',
        ]);

        $this->assertQueryParameters([
            'include' => 'author,site',
        ]);
    }

    public function testWithEncodingParameters()
    {
        $parameters = new EncodingParameters(
            ['author', 'site'],
            ['author' => ['first-name', 'surname'], 'site' => ['uri']]
        );

        $this->willSeeResource($this->post);
        $this->client->read('posts', '1', $parameters);

        $this->assertQueryParameters([
            'include' => 'author,site',
            'fields[author]' => 'first-name,surname',
            'fields[site]' => 'uri',
        ]);
    }

    public function testWithOptions()
    {
        $options = [
            'headers' => [
                'X-Foo' => 'Bar',
            ],
        ];

        $this->willSeeResource($this->post);
        $this->client->withOptions($options)->read('posts', '1');

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testError()
    {
        $this->willSeeErrors([], 404);
        $this->expectException(ClientException::class);
        $this->client->read('posts', '1');
    }
}
