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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Eloquent;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Country;
use DummyApp\JsonApi\Posts\Adapter;
use DummyApp\Post;
use DummyApp\User;

class ScopesTest extends TestCase
{

    /**
     * @var User
     */
    private $user;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->user = factory(User::class)->create();

        $this->app->afterResolving(Adapter::class, function (Adapter $adapter) {
            $adapter->addClosureScope(function ($query) {
                $query->where('author_id', $this->user->getKey());
            });
        });
    }

    public function testListAll(): void
    {
        $expected = factory(Post::class, 2)->create(['author_id' => $this->user->getKey()]);
        factory(Post::class, 3)->create();

        $response = $this
            ->jsonApi('posts')
            ->get('/api/v1/posts');

        $response->assertFetchedMany($expected);
    }

    public function testRead(): void
    {
        $post = factory(Post::class)->create(['author_id' => $this->user->getKey()]);

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/posts', $post));

        $response->assertFetchedOne([
            'type' => 'posts',
            'id' => (string) $post->getRouteKey(),
        ]);
    }

    public function testRead404(): void
    {
        $post = factory(Post::class)->create();

        $response = $this
            ->jsonApi()
            ->get(url('/api/v1/posts', $post));

        $response->assertStatus(404);
    }

    public function testReadToOne(): void
    {
        $this->markTestIncomplete('@todo');
    }

    public function testReadToOneRelationship(): void
    {
        $this->markTestIncomplete('@todo');
    }

    public function testReadToMany(): void
    {
        $country = factory(Country::class)->create();

        $this->user->country()->associate($country);
        $this->user->save();

        $expected = factory(Post::class, 2)->create(['author_id' => $this->user->getKey()]);

        factory(Post::class)->create([
            'author_id' => factory(User::class)->create([
                'country_id' => $country->getKey(),
            ]),
        ]);

        $url = url('/api/v1/countries', [$country, 'posts']);

        $response = $this
            ->jsonApi('posts')
            ->get($url);

        $response->assertFetchedMany($expected);
    }

    public function testReadToManyRelationship(): void
    {
        $country = factory(Country::class)->create();

        $this->user->country()->associate($country);
        $this->user->save();

        $expected = factory(Post::class, 2)->create(['author_id' => $this->user->getKey()]);

        factory(Post::class)->create([
            'author_id' => factory(User::class)->create([
                'country_id' => $country->getKey(),
            ]),
        ]);

        $url = url('/api/v1/countries', [$country, 'relationships', 'posts']);

        $response = $this
            ->jsonApi('posts')
            ->get($url);

        $response->assertFetchedToMany($expected);
    }
}
