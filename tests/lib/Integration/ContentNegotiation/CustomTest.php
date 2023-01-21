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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\ContentNegotiation;

use CloudCreativity\LaravelJsonApi\Facades\JsonApi;
use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;
use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Avatar;
use DummyApp\Post;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

class CustomTest extends TestCase
{

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

        $this->app->bind(
            'DummyApp\JsonApi\CustomContentNegotiator',
            TestContentNegotiator::class
        );
    }

    /**
     * Test that a default content negotiator can be specified at the
     * API level.
     */
    public function testDefault(): void
    {
        $post = factory(Post::class)->create();
        $uri = url('/api/v1/posts', $post);

        $this->withDefaultNegotiator()
            ->getJson($uri)
            ->assertStatus(200);
    }

    /**
     * If there is a default content negotiator, the resource negotiator
     * should still be used if there is one.
     */
    public function testApiDefaultDoesNotOverrideResourceNegotiator(): void
    {
        Storage::fake('local');

        $path = UploadedFile::fake()->image('avatar.jpg')->store('avatars');
        $avatar = factory(Avatar::class)->create(compact('path'));
        $uri = url('/api/v1/avatars', $avatar);

        $this
            ->withoutExceptionHandling()
            ->withDefaultNegotiator()
            ->get($uri, ['Accept' => 'image/*'])
            ->assertSuccessful()
            ->assertHeader('Content-Type', $avatar->media_type);
    }

    public function testResourceUsesNamedNegotiator(): void
    {
        $post = factory(Post::class)->create();
        $uri = url('/api/v1/posts', $post);

        $this->withResourceNegotiator()
            ->getJson($uri)
            ->assertStatus(200);
    }

    /**
     * @return CustomTest
     */
    private function withDefaultNegotiator(): self
    {
        Route::group([
            'namespace' => 'DummyApp\\Http\\Controllers',
        ], function () {
            JsonApi::register('v1')->defaultContentNegotiator('custom')->routes(function (RouteRegistrar $api) {
                $api->resource('avatars', ['controller' => true]);
                $api->resource('posts');
            });
        });

        return $this;
    }

    /**
     * @return CustomTest
     */
    private function withResourceNegotiator(): self
    {
        Route::group([
            'namespace' => 'DummyApp\\Http\\Controllers',
        ], function () {
            JsonApi::register('v1', ['content-negotiator' => 'foobar'], function (RouteRegistrar $api) {
                $api->resource('posts')->contentNegotiator('custom');
            });
        });

        return $this;
    }
}
