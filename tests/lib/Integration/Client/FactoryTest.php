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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use DummyApp\Post;
use GuzzleHttp\Client;

class FactoryTest extends TestCase
{

    /**
     * Uses the host in the JSON API config file.
     */
    public function testWithoutHost()
    {
        $client = $this->api()->client(['handler' => $this->handler]);
        $post = factory(Post::class)->make();

        $this->willSeeResource($post, 201);
        $client->createRecord($post);

        $this->assertSame(
            'http://localhost/api/v1/posts',
            (string) $this->mock->getLastRequest()->getUri()
        );
    }

    /**
     * If the options have a base URI, it should not be overridden.
     */
    public function testWithOptionsIncludingBaseUri()
    {
        $client = $this->api()->client([
            'handler' => $this->handler,
            'base_uri' => 'http://external.com/api/v1/',
        ]);

        $post = factory(Post::class)->make();

        $this->willSeeResource($post, 201);
        $client->createRecord($post);

        $this->assertSame(
            'http://external.com/api/v1/posts',
            (string) $this->mock->getLastRequest()->getUri()
        );
    }

    /**
     * The host that is provided can have a path as well.
     */
    public function testWithHostAndPath()
    {
        $client = $this->api()->client(
            'http://example.com/foo',
            ['handler' => $this->handler]
        );

        $post = factory(Post::class)->make();

        $this->willSeeResource($post, 201);
        $client->createRecord($post);

        $this->assertSame(
            'http://example.com/foo/api/v1/posts',
            (string) $this->mock->getLastRequest()->getUri()
        );
    }

    /**
     * A Guzzle client can be provided.
     */
    public function testWithGuzzleClient()
    {
        $guzzle = new Client([
            'handler' => $this->handler,
            'base_uri' => 'http://foobar.local/baz/bat/',
        ]);

        $client = $this->api()->client($guzzle);
        $post = factory(Post::class)->make();

        $this->willSeeResource($post, 201);
        $client->createRecord($post);

        $this->assertSame(
            'http://foobar.local/baz/bat/posts',
            (string) $this->mock->getLastRequest()->getUri()
        );
    }

}
