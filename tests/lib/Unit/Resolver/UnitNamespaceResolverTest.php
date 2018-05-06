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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Resolver;

use CloudCreativity\LaravelJsonApi\Resolver\UnitNamespaceResolver;
use DummyApp\Comment;
use DummyApp\Post;
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

    public function testNamedAuthorizer()
    {
        $this->assertSame('DummyApp\JsonApi\Authorizers\Generic', $this->resolver->getAuthorizerByName('generic'));
    }
}
