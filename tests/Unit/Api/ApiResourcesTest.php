<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Unit;

use CloudCreativity\LaravelJsonApi\Api\ApiResources;
use CloudCreativity\LaravelJsonApi\Api\ResourceMap;
use CloudCreativity\LaravelJsonApi\Tests\JsonApi;
use CloudCreativity\LaravelJsonApi\Tests\Models;
use DateTime;
use PHPUnit\Framework\TestCase;

class ApiResourcesTest extends TestCase
{

    public function testStandard()
    {
        $resources = $this->withResources([
            'posts' => Models\Post::class,
            'comments' => Models\Comment::class,
        ]);

        $this->assertSame([
            Models\Post::class => JsonApi\Posts\Schema::class,
            Models\Comment::class => JsonApi\Comments\Schema::class,
        ], $resources->getSchemas(), 'schemas');

        $this->assertSame([
            'posts' => JsonApi\Posts\Adapter::class,
            'comments' => JsonApi\Comments\Adapter::class,
        ], $resources->getAdapters(), 'adapters');

        $this->assertTrue($resources->has('posts'));
        $this->assertFalse($resources->has('tags'));
    }

    /**
     * If our resource type maps to multiple PHP record classes, we can specify them using an array
     * of PHP classes against the resource type.
     */
    public function testMultipleRecordFqns()
    {
        $resources = $this->withResources([
            'posts' => [Models\Post::class, DateTime::class],
            'comments' => Models\Comment::class,
        ]);

        $this->assertSame([
            Models\Post::class => JsonApi\Posts\Schema::class,
            DateTime::class => JsonApi\Posts\Schema::class,
            Models\Comment::class => JsonApi\Comments\Schema::class,
        ], $resources->getSchemas(), 'schemas');

        $this->assertSame([
            'posts' => JsonApi\Posts\Adapter::class,
            'comments' => JsonApi\Comments\Adapter::class,
        ], $resources->getAdapters(), 'adapters');

        $this->assertTrue($resources->has('posts'));
        $this->assertFalse($resources->has('tags'));
    }

    public function testMerge()
    {
        $resources = $this->withResources([
            'posts' => Models\Post::class,
        ]);

        $merged = $resources->merge($this->withResources([
            'comments' => Models\Comment::class,
        ]));

        $this->assertSame([
            Models\Post::class => JsonApi\Posts\Schema::class,
            Models\Comment::class => JsonApi\Comments\Schema::class,
        ], $merged->getSchemas(), 'schemas');
    }

    /**
     * @param array $resources
     * @param bool $byResource
     * @return ApiResources
     */
    private function withResources(array $resources, $byResource = true)
    {
        return ResourceMap::create(
            'CloudCreativity\LaravelJsonApi\Tests\JsonApi',
            $resources,
            $byResource
        )->all();
    }
}
