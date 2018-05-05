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

use CloudCreativity\LaravelJsonApi\Resolver\NamespaceResolver;
use PHPUnit\Framework\TestCase;

class NamespaceResolverTest extends TestCase
{

    /**
     * @var NamespaceResolver
     */
    private $resolver;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->resolver = new NamespaceResolver('App\JsonApi', [
            'posts' => 'App\Post',
            'comments' => 'App\Comment',
        ]);
    }

    /**
     * @return array
     */
    public function resourcesProvider()
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
        ];
    }

    /**
     * @param $resourceType
     * @param $type
     * @param $schema
     * @param $adapter
     * @param $validator
     * @param $auth
     * @dataProvider resourcesProvider
     */
    public function testResource($resourceType, $type, $schema, $adapter, $validator, $auth)
    {
        $exists = !is_null($type);

        $this->assertSame($exists, $this->resolver->isType($type));
        $this->assertSame($exists, $this->resolver->isResourceType($resourceType));

        $this->assertSame($exists ? $type : null, $this->resolver->getType($resourceType));
        $this->assertSame($exists ? $resourceType : null, $this->resolver->getResourceType($type));

        $this->assertSame($exists ? $schema : null, $this->resolver->getSchemaByType($type));
        $this->assertSame($schema, $this->resolver->getSchemaByResourceType($resourceType));

        $this->assertSame($exists ? $adapter : null, $this->resolver->getAdapterByType($type));
        $this->assertSame($adapter, $this->resolver->getAdapterByResourceType($resourceType));

        $this->assertSame($exists ? $validator : null, $this->resolver->getValidatorsByType($type));
        $this->assertSame($validator, $this->resolver->getValidatorsByResourceType($resourceType));

        $this->assertSame($exists ? $auth : null, $this->resolver->getAuthorizerByType($type));
        $this->assertSame($auth, $this->resolver->getAuthorizerByResourceType($resourceType));
    }

    public function testAll()
    {
        $this->assertEquals([
            'App\Post',
            'App\Comment',
        ], $this->resolver->getAllTypes());

        $this->assertEquals([
            'posts',
            'comments',
        ], $this->resolver->getAllResourceTypes());
    }

    public function testNamedAuthorizer()
    {
        $this->assertSame('App\JsonApi\GenericAuthorizer', $this->resolver->getAuthorizerByName('generic'));
    }
}
