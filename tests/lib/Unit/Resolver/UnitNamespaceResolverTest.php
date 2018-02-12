<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Resolver;

use CloudCreativity\LaravelJsonApi\Resolver\UnitNamespaceResolver;
use CloudCreativity\LaravelJsonApi\Tests\Models\Comment;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use PHPUnit\Framework\TestCase;

class UnitNamespaceResolverTest extends TestCase
{

    /**
     * @var UnitNamespaceResolver
     */
    private $resolver;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->resolver = new UnitNamespaceResolver('DummyApp\JsonApi', [
            'posts' => Post::class,
            'comments' => Comment::class,
        ]);
    }

    /**
     * @return array
     */
    public function resourcesProvider()
    {
        return [
            [
                Post::class,
                'posts',
                true,
                'DummyApp\JsonApi\Schemas\Post',
                'DummyApp\JsonApi\Adapters\Post',
                'DummyApp\JsonApi\Validators\Post',
                'DummyApp\JsonApi\Authorizers\Post',
            ]
        ];
    }

    /**
     * @param $type
     * @param $resourceType
     * @param $exists
     * @param $schema
     * @param $adapter
     * @param $validator
     * @param $auth
     * @dataProvider resourcesProvider
     */
    public function testResource($type, $resourceType, $exists, $schema, $adapter, $validator, $auth)
    {
        $this->assertSame($exists, $this->resolver->isType($type));
        $this->assertSame($exists, $this->resolver->isResourceType($resourceType));

        $this->assertSame($exists ? $type : null, $this->resolver->getType($resourceType));
        $this->assertSame($exists ? $resourceType : null, $this->resolver->getResourceType($type));

        $this->assertSame($schema, $this->resolver->getSchemaByType($type));
        $this->assertSame($schema, $this->resolver->getSchemaByResourceType($resourceType));

        $this->assertSame($adapter, $this->resolver->getAdapterByType($type));
        $this->assertSame($adapter, $this->resolver->getAdapterByResourceType($resourceType));

        $this->assertSame($validator, $this->resolver->getValidatorsByType($type));
        $this->assertSame($validator, $this->resolver->getValidatorsByResourceType($resourceType));

        $this->assertSame($auth, $this->resolver->getAuthorizerByType($type));
        $this->assertSame($auth, $this->resolver->getAuthorizerByResourceType($resourceType));
    }
}
