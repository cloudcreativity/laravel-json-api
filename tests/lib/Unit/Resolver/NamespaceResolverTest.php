<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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
    public static function byResourceProvider()
    {
        return [
            [
                'posts',
                'App\Post',
                'App\JsonApi\Posts',
            ],
            [
                'comments',
                'App\Comment',
                'App\JsonApi\Comments',
            ],
            [
                'tags',
                null,
                'App\JsonApi\Tags',
            ],
            [
                'dance-events',
                null,
                'App\JsonApi\DanceEvents',
            ],
            [
                'dance_events',
                null,
                'App\JsonApi\DanceEvents',
            ],
            [
                'danceEvents',
                null,
                'App\JsonApi\DanceEvents',
            ],
        ];
    }

    /**
     * @return array
     */
    public static function notByResourceProvider()
    {
        return [
            [
                'posts',
                'App\Post',
                'Post',
            ],
            [
                'comments',
                'App\Comment',
                'Comment',
            ],
            [
                'tags',
                null,
                'Tag',
            ],
            [
                'dance-events',
                null,
                'DanceEvent',
            ],
            [
                'dance_events',
                null,
                'DanceEvent',
            ],
            [
                'danceEvents',
                null,
                'DanceEvent',
            ],
        ];
    }

    /**
     * @return array
     */
    public static function genericAuthorizerProvider()
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
        ];
    }

    /**
     * @return array
     */
    public function genericContentNegotiator()
    {
        return [
            // By resource
            ['generic', 'App\JsonApi\GenericContentNegotiator', true],
            ['foo-bar', 'App\JsonApi\FooBarContentNegotiator', true],
            ['foo_bar', 'App\JsonApi\FooBarContentNegotiator', true],
            ['fooBar', 'App\JsonApi\FooBarContentNegotiator', true],
            // Not by resource
            ['generic', 'App\JsonApi\ContentNegotiators\GenericContentNegotiator', false],
            ['foo-bar', 'App\JsonApi\ContentNegotiators\FooBarContentNegotiator', false],
            ['foo_bar', 'App\JsonApi\ContentNegotiators\FooBarContentNegotiator', false],
            ['fooBar', 'App\JsonApi\ContentNegotiators\FooBarContentNegotiator', false],
        ];
    }

    /**
     * @param $resourceType
     * @param $type
     * @param $namespace
     * @dataProvider byResourceProvider
     */
    public function testByResource($resourceType, $type, $namespace)
    {
        $resolver = $this->createResolver(true);

        $this->assertResourceNamespace($resolver, $resourceType, $type, $namespace);
    }

    /**
     * @param $resourceType
     * @param $type
     * @param $singular
     * @dataProvider notByResourceProvider
     */
    public function testNotByResource($resourceType, $type, $singular)
    {
        $resolver = $this->createResolver(false);

        $this->assertUnitNamespace($resolver, $resourceType, $type,  'App\JsonApi', $singular);
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
     * @dataProvider genericAuthorizerProvider
     */
    public function testNamedAuthorizer($name, $expected, $byResource)
    {
        $resolver = $this->createResolver($byResource);
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
     * @param $contentNegotiator
     */
    private function assertResolver(
        $resolver,
        $resourceType,
        $type,
        $schema,
        $adapter,
        $validator,
        $auth,
        $contentNegotiator
    ) {
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

        $this->assertSame($contentNegotiator, $resolver->getContentNegotiatorByResourceType($resourceType));
    }

    /**
     * @param $resolver
     * @param $resourceType
     * @param $type
     * @param $namespace
     */
    private function assertResourceNamespace($resolver, $resourceType, $type, $namespace)
    {
        $this->assertResolver(
            $resolver,
            $resourceType,
            $type,
            "{$namespace}\Schema",
            "{$namespace}\Adapter",
            "{$namespace}\Validators",
            "{$namespace}\Authorizer",
            "{$namespace}\ContentNegotiator"
        );
    }

    /**
     * @param $resolver
     * @param $resourceType
     * @param $type
     * @param $namespace
     * @param $singular
     */
    private function assertUnitNamespace($resolver, $resourceType, $type, $namespace, $singular)
    {
        $this->assertResolver(
            $resolver,
            $resourceType,
            $type,
            "{$namespace}\Schemas\\{$singular}Schema",
            "{$namespace}\Adapters\\{$singular}Adapter",
            "{$namespace}\Validators\\{$singular}Validator",
            "{$namespace}\Authorizers\\{$singular}Authorizer",
            "{$namespace}\ContentNegotiators\\{$singular}ContentNegotiator"
        );
    }

}
