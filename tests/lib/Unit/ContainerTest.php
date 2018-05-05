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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit;

use CloudCreativity\LaravelJsonApi\Container;
use CloudCreativity\LaravelJsonApi\Contracts\Adapter\ResourceAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Auth\AuthorizerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Container\Container as IlluminateContainer;
use Neomerx\JsonApi\Contracts\Schema\SchemaProviderInterface;

class ContainerTest extends TestCase
{

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ResolverInterface
     */
    private $resolver;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|IlluminateContainer
     */
    private $illuminateContainer;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->resolver = $this->createMock(ResolverInterface::class);

        $this->resolver
            ->method('isResourceType')
            ->willReturnCallback(function ($resourceType) {
                return 'posts' === $resourceType;
            });

        $this->resolver
            ->method('getResourceType')
            ->with(\stdClass::class)
            ->willReturn('posts');

        $this->illuminateContainer = $this->createMock(IlluminateContainer::class);
        $this->container = new Container($this->illuminateContainer, $this->resolver);
    }

    public function testSchema()
    {
        $schema = $this->createMock(SchemaProviderInterface::class);

        $this->illuminateContainer
            ->expects($this->once())
            ->method('make')
            ->with(get_class($schema))
            ->willReturn($schema);

        $this->resolver
            ->expects($this->once())
            ->method('getSchemaByResourceType')
            ->with('posts')
            ->willReturn(get_class($schema));

        $this->assertSame($schema, $this->container->getSchemaByResourceType('posts'));
        $this->assertSame($schema, $this->container->getSchemaByType(\stdClass::class));
        $this->assertSame($schema, $this->container->getSchema(new \stdClass()));
    }

    public function testSchemaCreateReturnsNull()
    {
        $this->resolver->method('getSchemaByResourceType')->willReturn(SchemaProviderInterface::class);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(SchemaProviderInterface::class);
        $this->container->getSchemaByResourceType('posts');
    }

    public function testSchemaForInvalidResourceType()
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('comments');
        $this->container->getSchemaByResourceType('comments');
    }

    public function testSchemaIsNotASchema()
    {
        $this->resolver
            ->method('getSchemaByResourceType')
            ->willReturn(\stdClass::class);

        $this->illuminateContainer->method('make')->willReturn(new \stdClass());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stdClass');
        $this->container->getSchemaByResourceType('posts');
    }

    public function testAdapter()
    {
        $adapter = $this->createMock(ResourceAdapterInterface::class);

        $this->illuminateContainer
            ->expects($this->once())
            ->method('make')
            ->with(get_class($adapter))
            ->willReturn($adapter);

        $this->resolver
            ->expects($this->once())
            ->method('getAdapterByResourceType')
            ->willReturn(get_class($adapter));

        $this->assertSame($adapter, $this->container->getAdapterByResourceType('posts'));
        $this->assertSame($adapter, $this->container->getAdapterByType(\stdClass::class));
        $this->assertSame($adapter, $this->container->getAdapter(new \stdClass()));
    }

    public function testAdapterCreateReturnsNull()
    {
        $this->resolver->method('getAdapterByResourceType')->willReturn(ResourceAdapterInterface::class);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(ResourceAdapterInterface::class);
        $this->container->getAdapterByResourceType('posts');
    }

    /**
     * If a resource type is not valid, we expect `null` to be returned for the adapter.
     * This is so that we can detect an unknown resource type as we expect all known
     * resource types to have adapters.
     */
    public function testAdapterForInvalidResourceType()
    {
        $this->illuminateContainer->expects($this->never())->method('make');
        $this->assertNull($this->container->getAdapterByResourceType('comments'));
    }

    public function testAdapterIsNotAnAdapter()
    {
        $this->resolver
            ->method('getAdapterByResourceType')
            ->willReturn(\stdClass::class);

        $this->illuminateContainer->method('make')->willReturn(new \stdClass());
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stdClass');
        $this->container->getAdapterByResourceType('posts');
    }

    public function testValidators()
    {
        $validators = $this->createMock(ValidatorProviderInterface::class);

        $this->illuminateContainer
            ->expects($this->once())
            ->method('make')
            ->with(get_class($validators))
            ->willReturn($validators);

        $this->resolver
            ->expects($this->once())
            ->method('getValidatorsByResourceType')
            ->willReturn(get_class($validators));

        $this->assertSame($validators, $this->container->getValidatorsByResourceType('posts'));
        $this->assertSame($validators, $this->container->getValidatorsByType(\stdClass::class));
        $this->assertSame($validators, $this->container->getValidators(new \stdClass()));
    }

    public function testValidatorsCreateReturnsNull()
    {
        $this->resolver->method('getValidatorsByResourceType')->willReturn(ValidatorProviderInterface::class);
        $this->illuminateContainer->expects($this->never())->method('make')->with(ValidatorProviderInterface::class);

        $this->assertNull($this->container->getValidatorsByResourceType('posts'));
        $this->assertNull($this->container->getValidatorsByType(\stdClass::class));
        $this->assertNull($this->container->getValidators(new \stdClass()));
    }

    public function testValidatorsForInvalidResourceType()
    {
        $this->illuminateContainer->expects($this->never())->method('make');
        $this->assertNull($this->container->getValidatorsByResourceType('comments'));
    }

    public function testValidatorsAreNotValidators()
    {
        $this->resolver->method('getValidatorsByResourceType')->willReturn(\stdClass::class);
        $this->illuminateContainer->method('make')->willReturn(new \stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\stdClass::class);

        $this->container->getValidatorsByResourceType('posts');
    }

    public function testResourceAuthorizer()
    {
        $this->markTestSkipped('@todo');
        $authorizer = $this->createMock(AuthorizerInterface::class);

        $this->illuminateContainer
            ->expects($this->once())
            ->method('make')
            ->with(get_class($authorizer))
            ->willReturn($authorizer);

        $this->resolver
            ->expects($this->once())
            ->method('getAuthorizerByResourceType')
            ->willReturn(get_class($authorizer));

        $this->assertSame($authorizer, $this->container->getAuthorizerByResourceType('posts'));
        $this->assertSame($authorizer, $this->container->getAuthorizerByType(\stdClass::class));
        $this->assertSame($authorizer, $this->container->getAuthorizer(new \stdClass()));
    }

    public function testResourceAuthorizerCreateReturnsNull()
    {
        $this->resolver->method('getAuthorizerByResourceType')->willReturn(AuthorizerInterface::class);
        $this->illuminateContainer->expects($this->never())->method('make')->with(AuthorizerInterface::class);

        $this->assertNull($this->container->getAuthorizerByResourceType('posts'));
        $this->assertNull($this->container->getAuthorizerByType(\stdClass::class));
        $this->assertNull($this->container->getAuthorizer(new \stdClass()));
    }

    public function testResourceAuthorizerForInvalidResourceType()
    {
        $this->illuminateContainer->expects($this->never())->method('make');
        $this->assertNull($this->container->getAuthorizerByResourceType('comments'));
    }

    public function testResourceAuthorizerIsNotAuthorizer()
    {
        $this->resolver->method('getAuthorizerByResourceType')->willReturn(\stdClass::class);
        $this->illuminateContainer->method('make')->willReturn(new \stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\stdClass::class);

        $this->container->getAuthorizerByResourceType('posts');
    }

    public function testNamedAuthorizer()
    {
        $authorizer = $this->createMock(AuthorizerInterface::class);

        $this->illuminateContainer
            ->expects($this->once())
            ->method('make')
            ->with(get_class($authorizer))
            ->willReturn($authorizer);

        $this->resolver
            ->expects($this->once())
            ->method('getAuthorizerByName')
            ->with('generic')
            ->willReturn(get_class($authorizer));

        $this->assertSame($authorizer, $this->container->getAuthorizerByName('generic'));
    }

    public function testNamedAuthorizerIsNotAnAuthorizer()
    {
        $this->resolver->method('getAuthorizerByName')->willReturn(\stdClass::class);
        $this->illuminateContainer->method('make')->willReturn(new \stdClass());

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(\stdClass::class);

        $this->container->getAuthorizerByName('generic');
    }
}
