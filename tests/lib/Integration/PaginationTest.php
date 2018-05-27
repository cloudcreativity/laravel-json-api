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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use DummyApp\Post;

class PaginationTest extends TestCase
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
    protected function setUp()
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
        $response = $this->doSearch();
        $response->assertSearchResponse()->assertContainsOnly(['posts' => $posts->modelKeys()]);

        $response->assertJson([
            'meta' => [
                'page' => [
                    'current-page' => 1,
                    'per-page' => 10,
                    'from' => 1,
                    'to' => 4,
                    'total' => 4,
                    'last-page' => 1,
                ],
            ],
        ]);
    }

    /**
     * If the search does not match any models, then there are no pages.
     */
    public function testNoPages()
    {
        $response = $this->doSearch(['page' => ['number' => 1, 'size' => 3]]);
        $response->assertSearchedNone();

        $response->assertJson([
            'meta' => [
                'page' => [
                    'current-page' => 1,
                    'per-page' => 3,
                    'from' => null,
                    'to' => null,
                    'total' => 0,
                    'last-page' => 1,
                ],
            ],
            'links' => [
                'first' => $first = $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['number' => 1, 'size' => 3]]
                ),
                'last' => $first,
            ],
        ]);
    }

    public function testPage1()
    {
        $posts = factory(Post::class, 4)->create();
        $response = $this->doSearch(['page' => ['number' => 1, 'size' => 3]]);
        $response->assertSearchResponse()->assertContainsOnly(['posts' => $posts->take(3)->modelKeys()]);

        $response->assertJson([
            'meta' => [
                'page' => [
                    'current-page' => 1,
                    'per-page' => 3,
                    'from' => 1,
                    'to' => 3,
                    'total' => 4,
                    'last-page' => 2,
                ],
            ],
            'links' => [
                'first' => $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['number' => 1, 'size' => 3]]
                ),
                'next' => $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['number' => 2, 'size' => 3]]
                ),
                'last' => $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['number' => 2, 'size' => 3]]
                ),
            ],
        ]);
    }

    public function testPage2()
    {
        $posts = factory(Post::class, 4)->create();
        $response = $this->doSearch(['page' => ['number' => 2, 'size' => 3]]);
        $response->assertSearchResponse()->assertContainsOnly(['posts' => $posts->last()->getKey()]);

        $response->assertJson([
            'meta' => [
                'page' => [
                    'current-page' => 2,
                    'per-page' => 3,
                    'from' => 4,
                    'to' => 4,
                    'total' => 4,
                    'last-page' => 2,
                ],
            ],
            'links' => [
                'first' => $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['number' => 1, 'size' => 3]]
                ),
                'prev' => $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['number' => 1, 'size' => 3]]
                ),
                'last' => $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['number' => 2, 'size' => 3]]
                ),
            ],
        ]);
    }

    public function testCustomPageKeys()
    {
        factory(Post::class, 4)->create();
        $this->strategy->withPageKey('page')->withPerPageKey('limit');

        $response = $this->doSearch(['page' => ['page' => 1, 'limit' => 3]]);
        $response->assertSearchResponse();

        $response->assertJson([
            'links' => [
                'first' => $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['page' => 1, 'limit' => 3]]
                ),
                'next' => $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['page' => 2, 'limit' => 3]]
                ),
                'last' => $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['page' => 2, 'limit' => 3]]
                ),
            ],
        ]);
    }

    public function testSimplePagination()
    {
        factory(Post::class, 4)->create();
        $this->strategy->withSimplePagination();
        $response = $this->doSearch(['page' => ['number' => 1, 'size' => 3]]);
        $response->assertSearchResponse();

        $this->assertFalse(array_has($json = $response->json(), 'meta.page.total'));
        $this->assertFalse(array_has($json, 'meta.page.last-page'));
        $this->assertFalse(array_has($json, 'links.last'));

        $response->assertJson([
            'meta' => [
                'page' => [
                    'current-page' => 1,
                    'per-page' => 3,
                    'from' => 1,
                    'to' => 3,
                ],
            ],
            'links' => [
                'first' => $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['number' => 1, 'size' => 3]]
                ),
                'next' => $this->buildLink(
                    'http://localhost/api/v1/posts',
                    ['page' => ['number' => 2, 'size' => 3]]
                ),
            ],
        ]);
    }

    public function testCustomMetaKeys()
    {
        factory(Post::class, 4)->create();
        $this->strategy->withMetaKey('paginator')->withUnderscoredMetaKeys();
        $response = $this->doSearch(['page' => ['number' => 1, 'size' => 3]]);
        $response->assertSearchResponse();

        $response->assertJson([
            'meta' => [
                'paginator' => [
                    'current_page' => 1,
                    'per_page' => 3,
                    'from' => 1,
                    'to' => 3,
                    'total' => 4,
                    'last_page' => 2,
                ],
            ],
        ]);
    }

    public function testMetaNotNested()
    {
        factory(Post::class, 4)->create();
        $this->strategy->withMetaKey(null);

        $response = $this->doSearch(['page' => ['number' => 1, 'size' => 3]]);
        $response->assertSearchResponse();

        $response->assertJson([
            'meta' => [
                'current-page' => 1,
                'per-page' => 3,
                'from' => 1,
                'to' => 3,
                'total' => 4,
                'last-page' => 2,
            ],
        ]);
    }

    public function testPageParametersAreValidated()
    {
        factory(Post::class, 4)->create();

        $this->doSearch(['page' => ['number' => 1, 'size' => 999]])
            ->assertStatus(400)
            ->assertErrors()
            ->assertParameters('page.size');
    }

    /**
     * @param $path
     * @param array $params
     * @return string
     */
    private function buildLink($path, array $params)
    {
        return $path . '?' . http_build_query($params);
    }

}
