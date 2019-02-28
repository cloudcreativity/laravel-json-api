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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Pagination;

use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use DummyApp\Post;

class StandardPagingTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

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

        $this->doSearch()->assertFetchedPage($posts, null, [
            'current-page' => 1,
            'per-page' => 10,
            'from' => 1,
            'to' => 4,
            'total' => 4,
            'last-page' => 1,
        ]);
    }

    /**
     * If the search does not match any models, then there are no pages.
     */
    public function testNoPages()
    {
        $meta = [
            'current-page' => 1,
            'per-page' => 3,
            'from' => null,
            'to' => null,
            'total' => 0,
            'last-page' => 1,
        ];

        $links = [
            'first' => $first = $this->buildLink(
                '/api/v1/posts',
                ['page' => ['number' => 1, 'size' => 3]]
            ),
            'last' => $first,
        ];

        $this->doSearch(['page' => ['number' => 1, 'size' => 3]])
            ->assertFetchedEmptyPage($links, $meta);
    }

    public function testPage1()
    {
        $posts = factory(Post::class, 4)->create();

        $meta = [
            'current-page' => 1,
            'per-page' => 3,
            'from' => 1,
            'to' => 3,
            'total' => 4,
            'last-page' => 2,
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

        $this->doSearch(['page' => ['number' => 1, 'size' => 3]])
            ->assertFetchedPage($posts->take(3), $links, $meta);
    }

    public function testPage2()
    {
        $posts = factory(Post::class, 4)->create();

        $meta = [
            'current-page' => 2,
            'per-page' => 3,
            'from' => 4,
            'to' => 4,
            'total' => 4,
            'last-page' => 2,
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

        $this->doSearch(['page' => ['number' => 2, 'size' => 3]])
            ->assertFetchedPage($posts->last(), $links, $meta);
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

        $this->doSearch(['page' => ['page' => 1, 'limit' => 3]])
            ->assertLinks($links);
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

        $this->doSearch(['page' => ['number' => 1, 'size' => 3]])
            ->assertExactMeta(['page' => $meta])
            ->assertExactLinks($links);
    }

    public function testCustomMetaKeys()
    {
        $posts = factory(Post::class, 4)->create();
        $this->strategy->withMetaKey('paginator')->withUnderscoredMetaKeys();

        $meta = [
            'current_page' => 1,
            'per_page' => 3,
            'from' => 1,
            'to' => 3,
            'total' => 4,
            'last_page' => 2,
        ];

        $this->doSearch(['page' => ['number' => 1, 'size' => 3]])
            ->assertFetchedPage($posts->take(3), null, $meta, 'paginator');
    }

    public function testMetaNotNested()
    {
        factory(Post::class, 4)->create();
        $this->strategy->withMetaKey(null);

        $this->doSearch(['page' => ['number' => 1, 'size' => 3]])->assertExactMeta([
            'current-page' => 1,
            'per-page' => 3,
            'from' => 1,
            'to' => 3,
            'total' => 4,
            'last-page' => 2,
        ]);
    }

    public function testPageParametersAreValidated()
    {
        factory(Post::class, 4)->create();

        $this->doSearch(['page' => ['number' => 1, 'size' => 999]])->assertError(400, [
            'source' => ['parameter' => 'page.size']
        ]);
    }

}
