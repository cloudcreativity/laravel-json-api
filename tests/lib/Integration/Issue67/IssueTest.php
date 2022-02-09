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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Issue67;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\JsonApi\Posts\Schema as PostsSchema;
use DummyApp\Post;

class IssueTest extends TestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(PostsSchema::class, $this->app->make(Schema::class));
    }

    /**
     * If an exception is thrown while rendering resources within a page, the page
     * meta must not leak into the error response.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/67
     */
    public function test()
    {
        factory(Post::class)->create();

        $response = $this
            ->jsonApi()
            ->page(['number' => 1, 'size' => 5])
            ->get('/api/v1/posts');

        $response
            ->assertStatus(500);

        $response->assertExactJson([
            'errors' => [],
        ]);
    }
}
