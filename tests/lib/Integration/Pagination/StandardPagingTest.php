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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Pagination;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use DummyApp\Post;
use DummyApp\Video;

class StandardPagingTest extends TestCase
{

    /**
     * @var StandardStrategy
     */
    private $strategy;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->app->instance(StandardStrategy::class, $this->strategy = new StandardStrategy());
    }

    /**
     * An adapter's default pagination is used if no pagination parameters are sent.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/131
     */
    public function testDefaultPagination()
    {
        $posts = factory(Post::class, 4)->create();

        $meta = [
            'page' => [
                'current-page' => 1,
                'per-page' => 10,
                'from' => 1,
                'to' => 4,
                'total' => 4,
                'last-page' => 1,
            ],
        ];

        $response = $this
            ->jsonApi('posts')
            ->get('/api/v1/posts');

        $response->assertFetchedMany($posts)->assertExactMeta($meta);
    }

    /**
     * If the search does not match any models, then there are no pages.
     */
    public function testNoPages()
    {
        $meta = [
            'page' => [
                'current-page' => 1,
                'per-page' => 3,
                'from' => null,
                'to' => null,
                'total' => 0,
                'last-page' => 1,
            ],
        ];

        $links = [
            'first' => $first = $this->buildLink(
                '/api/v1/posts',
                ['page' => ['number' => 1, 'size' => 3]]
            ),
            'last' => $first,
        ];

        $response = $this
            ->jsonApi('posts')
            ->page(['number' => 1, 'size' => 3])
            ->get('/api/v1/posts');

        $response
            ->assertFetchedNone()
            ->assertExactMeta($meta)
            ->assertExactLinks($links);
    }

    public function testPage1()
    {
        $posts = factory(Post::class, 4)->create();

        $meta = [
            'page' => [
                'current-page' => 1,
                'per-page' => 3,
                'from' => 1,
                'to' => 3,
                'total' => 4,
                'last-page' => 2,
            ],
        ];

        $links = [
            'first' => $this->buildLink(
                '/api/v1/posts',
                ['page' => ['number' => 1, 'size' => 3]]
            ),
            'next' => $this->buildLink(
                '/api/v1/posts',
                ['page' => ['number' => 2, 'size' => 3]]
            ),
            'last' => $this->buildLink(
                '/api/v1/posts',
                ['page' => ['number' => 2, 'size' => 3]]
            ),
        ];

        $response = $this
            ->jsonApi('posts')
            ->page(['number' => 1, 'size' => 3])
            ->get('/api/v1/posts');

        $response
            ->assertFetchedMany($posts->take(3))
            ->assertExactMeta($meta)
            ->assertExactLinks($links);
    }

    public function testPage2()
    {
        $posts = factory(Post::class, 4)->create();

        $meta = [
            'page' => [
                'current-page' => 2,
                'per-page' => 3,
                'from' => 4,
                'to' => 4,
                'total' => 4,
                'last-page' => 2,
            ],
        ];

        $links = [
            'first' => $this->buildLink(
                '/api/v1/posts',
                ['page' => ['number' => 1, 'size' => 3]]
            ),
            'prev' => $this->buildLink(
                '/api/v1/posts',
                ['page' => ['number' => 1, 'size' => 3]]
            ),
            'last' => $this->buildLink(
                '/api/v1/posts',
                ['page' => ['number' => 2, 'size' => 3]]
            ),
        ];

        $response = $this
            ->jsonApi('posts')
            ->page(['number' => 2, 'size' => 3])
            ->get('/api/v1/posts');

        $response
            ->assertFetchedMany([$posts->last()])
            ->assertExactMeta($meta)
            ->assertExactLinks($links);
    }

    public function testPageWithReverseKey()
    {
        $posts = factory(Post::class, 4)->create()->reverse()->values();

        $response = $this
            ->jsonApi('posts')
            ->page(['number' => 1, 'size' => 3])
            ->sort('-id')
            ->get('/api/v1/posts');

        $response->assertFetchedManyInOrder($posts->take(3));
    }

    /**
     * If we are sorting by a column that might not be unique, we expect
     * the page to always be returned in a particular order i.e. by the
     * key column.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/313
     */
    public function testDeterministicOrder()
    {
        $first = factory(Video::class)->create([
            'created_at' => Carbon::now()->subWeek(),
        ]);

        $f = factory(Video::class)->create([
            'uuid' => 'f3b3bea3-dca0-4ef9-b06c-43583a7e6118',
            'created_at' => Carbon::now()->subHour(),
        ]);

        $d = factory(Video::class)->create([
            'uuid' => 'd215f35c-feb7-4cc5-9631-61742f00d0b2',
            'created_at' => $f->created_at,
        ]);

        $c = factory(Video::class)->create([
            'uuid' => 'cbe17134-d7e2-4509-ba2c-3b3b5e3b2cbe',
            'created_at' => $f->created_at,
        ]);

        $response = $this
            ->jsonApi('videos')
            ->page(['number' => '1', 'size' => '3'])
            ->sort('createdAt')
            ->get('/api/v1/videos');

        $response->assertFetchedManyInOrder([$first, $c, $d]);
    }

    public function testCustomPageKeys()
    {
        factory(Post::class, 4)->create();
        $this->strategy->withPageKey('page')->withPerPageKey('limit');

        $links = [
            'first' => $this->buildLink(
                '/api/v1/posts',
                ['page' => ['page' => 1, 'limit' => 3]]
            ),
            'next' => $this->buildLink(
                '/api/v1/posts',
                ['page' => ['page' => 2, 'limit' => 3]]
            ),
            'last' => $this->buildLink(
                '/api/v1/posts',
                ['page' => ['page' => 2, 'limit' => 3]]
            ),
        ];

        $response = $this
            ->jsonApi('posts')
            ->page(['page' => '1', 'limit' => '3'])
            ->get('/api/v1/posts');

        $response->assertLinks($links);
    }

    public function testSimplePagination()
    {
        factory(Post::class, 4)->create();
        $this->strategy->withSimplePagination();

        $meta = [
            'current-page' => 1,
            'per-page' => 3,
            'from' => 1,
            'to' => 3,
        ];

        $links = [
            'first' => $this->buildLink(
                '/api/v1/posts',
                ['page' => ['number' => 1, 'size' => 3]]
            ),
            'next' => $this->buildLink(
                '/api/v1/posts',
                ['page' => ['number' => 2, 'size' => 3]]
            ),
        ];

        $response = $this
            ->jsonApi('posts')
            ->page(['number' => '1', 'size' => '3'])
            ->get('/api/v1/posts');

        $response
            ->assertExactMeta(['page' => $meta])
            ->assertExactLinks($links);
    }

    public function testCustomMetaKeys()
    {
        $posts = factory(Post::class, 4)->create();
        $this->strategy->withMetaKey('paginator')->withUnderscoredMetaKeys();

        $meta = [
            'paginator' => [
                'current_page' => 1,
                'per_page' => 3,
                'from' => 1,
                'to' => 3,
                'total' => 4,
                'last_page' => 2,
            ],
        ];

        $response = $this
            ->jsonApi('posts')
            ->page(['number' => '1', 'size' => '3'])
            ->get('/api/v1/posts');

        $response
            ->assertFetchedMany($posts->take(3))
            ->assertExactMeta($meta);
    }

    public function testMetaNotNested()
    {
        factory(Post::class, 4)->create();
        $this->strategy->withMetaKey(null);

        $meta = [
            'current-page' => 1,
            'per-page' => 3,
            'from' => 1,
            'to' => 3,
            'total' => 4,
            'last-page' => 2,
        ];

        $response = $this
            ->jsonApi('posts')
            ->page(['number' => '1', 'size' => '3'])
            ->get('/api/v1/posts');

        $response->assertExactMeta($meta);
    }

    public function testPageParametersAreValidated()
    {
        factory(Post::class, 4)->create();

        $response = $this
            ->jsonApi('posts')
            ->page(['number' => '1', 'size' => '999'])
            ->get('/api/v1/posts');

        $response->assertError(400, [
            'source' => ['parameter' => 'page.size']
        ]);
    }

}
