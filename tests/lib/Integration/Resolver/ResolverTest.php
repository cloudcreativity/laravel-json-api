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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Resolver;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\JsonApi\Posts\Adapter;
use DummyApp\Post;
use Illuminate\Support\Facades\Route;

class ResolverTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'foobars';

    /**
     * @var bool
     */
    protected $appRoutes = false;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind('adapters:foobars', Adapter::class);
        $this->app->bind('schemas:foobars', Schema::class);

        Route::group([
            'namespace' => 'DummyApp\\Http\\Controllers',
        ], function () {
            JsonApi::register('v1', [], function (RouteRegistrar $api) {
                $api->resource('foobars');
            });
        });
    }

    /**
     * Use a resolver returned from a container binding.
     */
    public function testBinding()
    {
        config()->set('json-api-v1.resolver', 'my-resolver');

        $this->app->instance('my-resolver', new CustomResolver([
            'foobars' => Post::class,
        ]));

        $post = factory(Post::class)->create();

        $this->doRead($post)->assertRead([
            'type' => 'foobars',
            'id' => $post,
            'attributes' => [
                'title' => $post->title,
            ],
        ]);
    }

    /**
     * Create a resolver via a factory.
     */
    public function testViaFactory()
    {
        config()->set('json-api-v1.resolver', CreateCustomResolver::class);
        config()->set('json-api-v1.resources', [
            'foobars' => Post::class,
        ]);

        $post = factory(Post::class)->create();

        $this->doRead($post)->assertRead([
            'type' => 'foobars',
            'id' => $post,
            'attributes' => [
                'title' => $post->title,
            ],
        ]);
    }
}
