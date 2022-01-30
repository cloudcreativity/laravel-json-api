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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Routing;

use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Post;
use DummyApp\User;
use Illuminate\Support\Arr;

/**
 * Class RouteParameterTest
 *
 * @see https://github.com/cloudcreativity/laravel-json-api/issues/348
 */
class RouteParameterTest extends TestCase
{

    /**
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('json-api-v1.url.namespace', '/foo/{tenant}/api');

        $this->withFluentRoutes()->routes(function (RouteRegistrar $api) {
            $api->resource('posts');
        });
    }

    public function test(): void
    {
        $post = factory(Post::class)->create();
        $url = url('/foo/bar/api/posts', $post);

        $expected = [
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
            'links' => [
                'self' => $url,
            ],
        ];

        $response = $this->jsonApi()->get($url);

        $response->assertFetchedOne($expected);
    }

    public function testManual(): void
    {
        $post = factory(Post::class)->create();
        $data = json_api(null, null, ['tenant' => 'bar'])->encoder()->serializeData($post);

        $this->assertSame(
            url('/foo/bar/api/posts', $post),
            Arr::get($data, 'data.links.self')
        );
    }

    public function testModelBinding(): void
    {
        $post = factory(Post::class)->create();
        $user = factory(User::class)->create();
        $data = json_api(null, null, ['tenant' => $user])->encoder()->serializeData($post);

        $this->assertSame(
            url('/foo/2/api/posts', $post),
            Arr::get($data, 'data.links.self')
        );
    }
}
