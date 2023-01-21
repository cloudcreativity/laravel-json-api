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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Encoder\Encoder;
use DummyApp\Post;
use Illuminate\Support\Arr;

class EncodingTest extends TestCase
{

    /**
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * If the URL host is set to `null`, we expect the request host to be prepended to links.
     */
    public function testRequestedResourceHasRequestHost()
    {
        $id = factory(Post::class)->create()->getRouteKey();
        config()->set('json-api-v1.url.host', null);

        $response = $this
            ->withAppRoutes()
            ->jsonApi()
            ->get("http://www.example.com/api/v1/posts/$id");

        $json = $response
            ->assertStatus(200)
            ->json();

        $this->assertSelfLink("http://www.example.com/api/v1/posts/$id", $json);
    }

    /**
     * If the URL host is set to `false`, we do not expect a host to be prepended to links.
     */
    public function testRequestedResourceDoesNotHaveHost()
    {
        $id = factory(Post::class)->create()->getRouteKey();
        config()->set('json-api-v1.url.host', false);

        $response = $this
            ->withAppRoutes()
            ->jsonApi()
            ->get("http://www.example.com/api/v1/posts/$id");

        $json = $response
            ->assertStatus(200)
            ->json();

        $this->assertSelfLink("/api/v1/posts/$id", $json);
    }

    /**
     * If there is no URL namespace, the URL must be properly formed.
     */
    public function testRequestResourceDoesNotHaveUrlNamespace()
    {
        $id = factory(Post::class)->create()->getRouteKey();
        config()->set('json-api-v1.url.namespace', null);

        $response = $this
            ->withAppRoutes()
            ->jsonApi()
            ->get("http://www.example.com/posts/$id");

        $json = $response
            ->assertStatus(200)
            ->json();

        $this->assertSelfLink("http://www.example.com/posts/$id", $json);
    }

    /**
     * If there is no URL namespace, the URL must be properly formed.
     */
    public function testRequestResourceHasEmptyUrlNamespace()
    {
        $id = factory(Post::class)->create()->getRouteKey();
        config()->set('json-api-v1.url.namespace', '');

        $response = $this
            ->withAppRoutes()
            ->jsonApi()
            ->get("http://www.example.com/posts/$id");

        $json = $response
            ->assertStatus(200)
            ->json();

        $this->assertSelfLink("http://www.example.com/posts/$id", $json);
    }

    /**
     * If there is no URL host and namespace, the URL must be properly formed.
     */
    public function testRequestResourceDoesNotHaveHostAndUrlNamespace()
    {
        $id = factory(Post::class)->create()->getRouteKey();
        config()->set('json-api-v1.url.host', false);
        config()->set('json-api-v1.url.namespace', null);

        $response = $this
            ->withAppRoutes()
            ->jsonApi()
            ->get("http://www.example.com/posts/$id");

        $json = $response
            ->assertStatus(200)
            ->json();

        $this->assertSelfLink("/posts/$id", $json);
    }

    /**
     * When encoding outside of a request and the URL host is set to `null`, we expect the
     * `app.url` config setting to be used.
     */
    public function testSerializedResourceHasAppHost()
    {
        $post = factory(Post::class)->create();

        config()->set('app.url', $host = 'http://www.example.com');
        config()->set('json-api-v1.url.host', null);

        $json = json_api()->encoder()->serializeData($post);
        $this->assertSelfLink("http://www.example.com/api/v1/posts/{$post->getRouteKey()}", $json);
    }

    /**
     * When encoding outside of a request and the URL host is set to a specified host,
     * we expect that to be used.
     */
    public function testSerializedResourceHasSpecificHost()
    {
        $post = factory(Post::class)->create();

        config()->set('app.url', 'http://localhost');
        config()->set('json-api-v1.url.host', $host = 'http://www.example.com');

        $json = json_api()->encoder()->serializeData($post);
        $this->assertSelfLink("http://www.example.com/api/v1/posts/{$post->getRouteKey()}", $json);
    }

    /**
     * When encoding outside of a request and the URL host is set to `false`, we expect
     * no host in the serialized data.
     */
    public function testSerializedResourceDoesNotHaveAppHost()
    {
        $post = factory(Post::class)->create();

        config()->set('app.url', 'http://www.example.com');
        config()->set('json-api-v1.url.host', false);

        $json = json_api()->encoder()->serializeData($post);
        $this->assertSelfLink("/api/v1/posts/{$post->getRouteKey()}", $json);
    }

    /**
     * @param $link
     * @param array $json
     */
    private function assertSelfLink($link, array $json)
    {
        $actual = Arr::get($json, 'data.links.self');

        $this->assertSame($link, $actual);
    }
}
