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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Unit\Resolver;

use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Resolver\NamespaceResolver;
use PHPUnit\Framework\TestCase;

class NamespaceResolverTest extends TestCase
{

    /**
     * @return array
     */
    public function byResourceProvider()
    {
        return [
            [
                'posts',
                'App\Post',
                'App\JsonApi\Posts\Schema',
                'App\JsonApi\Posts\Adapter',
                'App\JsonApi\Posts\Validators',
                'App\JsonApi\Posts\Authorizer',
            ],
            [
                'comments',
                'App\Comment',
                'App\JsonApi\Comments\Schema',
                'App\JsonApi\Comments\Adapter',
                'App\JsonApi\Comments\Validators',
                'App\JsonApi\Comments\Authorizer',
            ],
            [
                'tags',
                null,
                'App\JsonApi\Tags\Schema',
                'App\JsonApi\Tags\Adapter',
                'App\JsonApi\Tags\Validators',
                'App\JsonApi\Tags\Authorizer',
            ],
            [
                'dance-events',
                null,
                'App\JsonApi\DanceEvents\Schema',
                'App\JsonApi\DanceEvents\Adapter',
                'App\JsonApi\DanceEvents\Validators',
                'App\JsonApi\DanceEvents\Authorizer',
            ],
            [
                'dance_events',
                null,
                'App\JsonApi\DanceEvents\Schema',
                'App\JsonApi\DanceEvents\Adapter',
                'App\JsonApi\DanceEvents\Validators',
                'App\JsonApi\DanceEvents\Authorizer',
            ],
            [
                'danceEvents',
                null,
                'App\JsonApi\DanceEvents\Schema',
                'App\JsonApi\DanceEvents\Adapter',
                'App\JsonApi\DanceEvents\Validators',
                'App\JsonApi\DanceEvents\Authorizer',
            ],
        ];
    }

    /**
     * @return array
     */
    public function notByResourceProvider()
    {
        return [
            [
                'posts',
                'App\Post',
                'App\JsonApi\Schemas\PostSchema',
                'App\JsonApi\Adapters\PostAdapter',
                'App\JsonApi\Validators\PostValidator',
                'App\JsonApi\Authorizers\PostAuthorizer',
            ],
            [
                'comments',
                'App\Comment',
                'App\JsonApi\Schemas\CommentSchema',
                'App\JsonApi\Adapters\CommentAdapter',
                'App\JsonApi\Validators\CommentValidator',
                'App\JsonApi\Authorizers\CommentAuthorizer',
            ],
            [
                'tags',
                null,
                'App\JsonApi\Schemas\TagSchema',
                'App\JsonApi\Adapters\TagAdapter',
                'App\JsonApi\Validators\TagValidator',
                'App\JsonApi\Authorizers\TagAuthorizer',
            ],
            [
                'dance-events',
                null,
                'App\JsonApi\Schemas\DanceEventSchema',
                'App\JsonApi\Adapters\DanceEventAdapter',
                'App\JsonApi\Validators\DanceEventValidator',
                'App\JsonApi\Authorizers\DanceEventAuthorizer',
            ],
            [
                'dance_events',
                null,
                'App\JsonApi\Schemas\DanceEventSchema',
                'App\JsonApi\Adapters\DanceEventAdapter',
                'App\JsonApi\Validators\DanceEventValidator',
                'App\JsonApi\Authorizers\DanceEventAuthorizer',
            ],
            [
                'danceEvents',
                null,
                'App\JsonApi\Schemas\DanceEventSchema',
                'App\JsonApi\Adapters\DanceEventAdapter',
                'App\JsonApi\Validators\DanceEventValidator',
                'App\JsonApi\Authorizers\DanceEventAuthorizer',
            ],
        ];
    }

    /**
     * @return array
     */
    public function notByResourceWithoutTypeProvider()
    {
        return [
            [
                'posts',
                'App\Post',
                'App\JsonApi\Schemas\Post',
                'App\JsonApi\Adapters\Post',
                'App\JsonApi\Validators\Post',
                'App\JsonApi\Authorizers\Post',
            ],
            [
                'comments',
                'App\Comment',
                'App\JsonApi\Schemas\Comment',
                'App\JsonApi\Adapters\Comment',
                'App\JsonApi\Validators\Comment',
                'App\JsonApi\Authorizers\Comment',
            ],
            [
                'tags',
                null,
                'App\JsonApi\Schemas\Tag',
                'App\JsonApi\Adapters\Tag',
                'App\JsonApi\Validators\Tag',
                'App\JsonApi\Authorizers\Tag',
            ],
            [
                'dance-events',
                null,
                'App\JsonApi\Schemas\DanceEvent',
                'App\JsonApi\Adapters\DanceEvent',
                'App\JsonApi\Validators\DanceEvent',
                'App\JsonApi\Authorizers\DanceEvent',
            ],
            [
                'dance_events',
                null,
                'App\JsonApi\Schemas\DanceEvent',
                'App\JsonApi\Adapters\DanceEvent',
                'App\JsonApi\Validators\DanceEvent',
                'App\JsonApi\Authorizers\DanceEvent',
            ],
            [
                'danceEvents',
                null,
                'App\JsonApi\Schemas\DanceEvent',
                'App\JsonApi\Adapters\DanceEvent',
                'App\JsonApi\Validators\DanceEvent',
                'App\JsonApi\Authorizers\DanceEvent',
            ],
        ];
    }

    /**
     * @return array
     */
    public function genericAuthorizerProvider()
    {
        return [
            // By resource
            ['generic', 'App\JsonApi\GenericAuthorizer', true],
            ['foo-bar', 'App\JsonApi\FooBarAuthorizer', true],
            ['foo_bar', 'App\JsonApi\FooBarAuthorizer', true],
            ['fooBar', 'App\JsonApi\FooBarAuthorizer', true],
            // Not by resource
            ['generic', 'App\JsonApi\Authorizers\GenericAuthorizer', false],
            ['foo-bar', 'App\JsonApi\Authorizers\FooBarAuthorizer', false],
            ['foo_bar', 'App\JsonApi\Authorizers\FooBarAuthorizer', false],
            ['fooBar', 'App\JsonApi\Authorizers\FooBarAuthorizer', false],
            // Not by resource without type appended:
            ['generic', 'App\JsonApi\Authorizers\Generic', false, false],
            ['foo-bar', 'App\JsonApi\Authorizers\FooBar', false, false],
            ['foo_bar', 'App\JsonApi\Authorizers\FooBar', false, false],
            ['fooBar', 'App\JsonApi\Authorizers\FooBar', false, false],
        ];
    }

    /**
     * @param $resourceType
     * @param $type
     * @param $schema
     * @param $adapter
     * @param $validator
     * @param $auth
     * @dataProvider byResourceProvider
     */
    public function testByResource($resourceType, $type, $schema, $adapter, $validator, $auth)
    {
        $resolver = $this->createResolver(true);

        $this->assertResolver($resolver, $resourceType, $type, $schema, $adapter, $validator, $auth);
    }

    /**
     * @param $resourceType
     * @param $type
     * @param $schema
     * @param $adapter
     * @param $validator
     * @param $auth
     * @dataProvider notByResourceProvider
     */
    public function testNotByResource($resourceType, $type, $schema, $adapter, $validator, $auth)
    {
        $resolver = $this->createResolver(false);

        $this->assertResolver($resolver, $resourceType, $type, $schema, $adapter, $validator, $auth);
    }

    /**
     * @param $resourceType
     * @param $type
     * @param $schema
     * @param $adapter
     * @param $validator
     * @param $auth
     * @dataProvider notByResourceWithoutTypeProvider
     */
    public function testNotByResourceWithoutType($resourceType, $type, $schema, $adapter, $validator, $auth)
    {
        $resolver = $this->createResolver(false, false);

        $this->assertResolver($resolver, $resourceType, $type, $schema, $adapter, $validator, $auth);
    }

    public function testAll()
    {
        $resolver = $this->createResolver();

        $this->assertEquals([
            'App\Post',
            'App\Comment',
        ], $resolver->getAllTypes());

        $this->assertEquals([
            'posts',
            'comments',
        ], $resolver->getAllResourceTypes());
    }

    /**
     * @param $name
     * @param $expected
     * @param $byResource
     * @param $withType
     * @dataProvider genericAuthorizerProvider
     */
    public function testNamedAuthorizer($name, $expected, $byResource, $withType = true)
    {
        $resolver = $this->createResolver($byResource, $withType);
        $this->assertSame($expected, $resolver->getAuthorizerByName($name));
    }

    public function testTrimsNamespace()
    {
        $resolver = new NamespaceResolver('App\JsonApi\\', [
            'posts' => 'App\Post',
        ]);

        $this->assertSame('App\JsonApi\Posts\Adapter', $resolver->getAdapterByResourceType('posts'));
    }

    /**
     * @param bool $byResource
     * @param bool $withType
     * @return NamespaceResolver
     */
    private function createResolver($byResource = true, $withType = true)
    {
        return new NamespaceResolver('App\JsonApi', [
            'posts' => 'App\Post',
            'comments' => 'App\Comment',
        ], $byResource, $withType);
    }

    /**
     * @param ResolverInterface $resolver
     * @param $resourceType
     * @param $type
     * @param $schema
     * @param $adapter
     * @param $validator
     * @param $auth
     */
    private function assertResolver($resolver, $resourceType, $type, $schema, $adapter, $validator, $auth)
    {
        $exists = !is_null($type);

        $this->assertSame($exists, $resolver->isType($type));
        $this->assertSame($exists, $resolver->isResourceType($resourceType));

        $this->assertSame($exists ? $type : null, $resolver->getType($resourceType));
        $this->assertSame($exists ? $resourceType : null, $resolver->getResourceType($type));

        $this->assertSame($exists ? $schema : null, $resolver->getSchemaByType($type));
        $this->assertSame($schema, $resolver->getSchemaByResourceType($resourceType));

        $this->assertSame($exists ? $adapter : null, $resolver->getAdapterByType($type));
        $this->assertSame($adapter, $resolver->getAdapterByResourceType($resourceType));

        $this->assertSame($exists ? $validator : null, $resolver->getValidatorsByType($type));
        $this->assertSame($validator, $resolver->getValidatorsByResourceType($resourceType));

        $this->assertSame($exists ? $auth : null, $resolver->getAuthorizerByType($type));
        $this->assertSame($auth, $resolver->getAuthorizerByResourceType($resourceType));
    }
}
