<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Issue67;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\JsonApi\Posts\Schema as PostsSchema;
use DummyApp\Post;

class IssueTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @return void
     */
    protected function setUp()
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
            ->doSearch(['page' => ['number' => 1, 'size' => 5]])
            ->assertStatus(500);

        $response->assertExactJson([
            'errors' => [],
        ]);
    }
}
