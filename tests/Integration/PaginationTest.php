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

    public function testPage()
    {
        $this->doSearch(['page' => ['number' => 1, 'size' => 3]])
            ->assertSearchResponse()
            ->assertContainsOnly(['posts' => $this->posts->take(3)->modelKeys()]);

        $this->markTestIncomplete('@todo test page meta.');
        $this->markTestIncomplete('@todo test page links.');
    }

    public function testCustomPageKeys()
    {
        $this->strategy->withPageKey('page')->withPerPageKey('limit');

        $this->doSearch(['page' => ['page' => 1, 'limit' => 3]])->assertSearchResponse();
        $this->markTestIncomplete('@todo test page meta.');
        $this->markTestIncomplete('@todo test page links.');
    }

    public function testSimplePagination()
    {
        $this->strategy->withSimplePagination();
        $this->doSearch(['page' => ['page' => 1, 'limit' => 3]])->assertSearchResponse();
        $this->markTestIncomplete('@todo test page meta.');
        $this->markTestIncomplete('@todo test page links.');
    }

    public function testCustomMetaKeys()
    {
        $this->strategy->withMetaKey('paginator')->withUnderscoredMetaKeys();
        $this->doSearch(['page' => ['page' => 1, 'limit' => 3]])->assertSearchResponse();
        $this->markTestIncomplete('@todo test page meta.');
    }

    public function testMetaNotNested()
    {
        $this->strategy->withMetaKey(null);
        $this->doSearch(['page' => ['page' => 1, 'limit' => 3]])->assertSearchResponse();
        $this->markTestIncomplete('@todo test page meta.');
    }

    public function testPageParametersAreValidated()
    {
        $this->doSearch(['page' => ['number' => 1, 'size' => 999]])
            ->assertStatusCode(400)
            ->assertErrors()
            ->assertParameters('page.size');
    }

}
