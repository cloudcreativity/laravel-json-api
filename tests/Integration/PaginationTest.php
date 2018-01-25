<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use Illuminate\Database\Eloquent\Collection;

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
     * @var Collection
     */
    private $posts;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->withDefaultApi([], function (ApiGroup $api) {
            $api->resource('posts');
        });

        $this->app->instance(StandardStrategy::class, $this->strategy = new StandardStrategy());
        $this->posts = factory(Post::class, 4)->create();
    }

    /**
     * An adapter's default pagination is used if no pagination parameters are sent.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/131
     */
    public function testDefaultPagination()
    {
        $response = $this->doSearch();
        $response->assertSearchResponse()->assertContainsOnly(['posts' => $this->posts->modelKeys()]);

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

    public function testPage1()
    {
        $response = $this->doSearch(['page' => ['number' => 1, 'size' => 3]]);
        $response->assertSearchResponse()->assertContainsOnly(['posts' => $this->posts->take(3)->modelKeys()]);

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
        $response = $this->doSearch(['page' => ['number' => 2, 'size' => 3]]);
        $response->assertSearchResponse()->assertContainsOnly(['posts' => $this->posts->last()->getKey()]);

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
