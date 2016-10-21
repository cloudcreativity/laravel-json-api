<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Routing;

use CloudCreativity\LaravelJsonApi\Contracts\Document\LinkFactoryInterface;
use CloudCreativity\LaravelJsonApi\TestCase;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Route;
use PHPUnit_Framework_MockObject_MockObject as Mock;

/**
 * Class ResourceRegistrarTest
 * @package CloudCreativity\LaravelJsonApi
 */
final class ResourceRegistrarTest extends TestCase
{

    /**
     * @var Mock
     */
    private $mock;

    /**
     * @var ResourceRegistrar
     */
    private $registrar;

    /**
     * @var int
     */
    private $index = 0;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        /** @var Registrar $registrar */
        $registrar = $this->getMockBuilder(Registrar::class)->getMock();

        $this->registrar = new ResourceRegistrar($registrar);
        $this->mock = $registrar;
    }

    public function testRegistration()
    {
        $this->willRegister('posts', 'PostsController');
        $this->registrar->resource('posts');
    }

    public function testRegistrationWithController()
    {
        $this->willRegister('posts', $controller = 'MyNamespace/PostsController');
        $this->registrar->resource('posts', $controller);
    }

    public function testRegistrationWithSlugResourceType()
    {
        $this->willRegister('user-accounts', 'UserAccountsController');
        $this->registrar->resource('user-accounts');
    }

    public function testRegistrationWithSnakeResourceType()
    {
        $this->willRegister('user_accounts', 'UserAccountsController');
        $this->registrar->resource('user_accounts');
    }

    /**
     * @param $resourceType
     * @param $expectedController
     */
    private function willRegister($resourceType, $expectedController)
    {
        $index = sprintf(LinkFactoryInterface::ROUTE_NAME_INDEX, $resourceType);
        $resource = sprintf(LinkFactoryInterface::ROUTE_NAME_RESOURCE, $resourceType);
        $relatedResource = sprintf(LinkFactoryInterface::ROUTE_NAME_RELATED_RESOURCE, $resourceType);
        $relationship = sprintf(LinkFactoryInterface::ROUTE_NAME_RELATIONSHIPS, $resourceType);

        $this->willSee($resourceType, 'GET', "/$resourceType", $expectedController, 'index', $index);
        $this->willSee($resourceType, 'POST', "/$resourceType", $expectedController, 'create');
        $this->willSee($resourceType, 'GET', "/$resourceType/{resource_id}", $expectedController, 'read', $resource);
        $this->willSee($resourceType, 'PATCH', "/$resourceType/{resource_id}", $expectedController, 'update');
        $this->willSee($resourceType, 'DELETE', "/$resourceType/{resource_id}", $expectedController, 'delete');
        $this->willSee($resourceType, 'GET', "/$resourceType/{resource_id}/{relationship_name}", $expectedController, 'readRelatedResource', $relatedResource);
        $this->willSee($resourceType, 'GET', "/$resourceType/{resource_id}/relationships/{relationship_name}", $expectedController, 'readRelationship', $relationship);
        $this->willSee($resourceType, 'PATCH', "/$resourceType/{resource_id}/relationships/{relationship_name}", $expectedController, 'replaceRelationship');
        $this->willSee($resourceType, 'POST', "/$resourceType/{resource_id}/relationships/{relationship_name}", $expectedController, 'addToRelationship');
        $this->willSee($resourceType, 'DELETE', "/$resourceType/{resource_id}/relationships/{relationship_name}", $expectedController, 'removeFromRelationship');
    }

    /**
     * @param $resourceType
     * @param $httpMethod
     * @param $uri
     * @param $controller
     * @param $controllerMethod
     * @param $as
     */
    private function willSee(
        $resourceType,
        $httpMethod,
        $uri,
        $controller,
        $controllerMethod,
        $as = null
    ) {
        $options = ['uses' => $controller . '@' . $controllerMethod];

        if ($as) {
            $options['as'] = $as;
        }

        $this->mock
            ->expects($this->at($this->index))
            ->method(strtolower($httpMethod))
            ->with($uri, $options)
            ->willReturn($this->route($resourceType));

        $this->index++;
    }

    /**
     * @param $resourceType
     * @return Mock
     */
    private function route($resourceType)
    {
        $route = $this
            ->getMockBuilder(Route::class)
            ->disableOriginalConstructor()
            ->getMock();

        $route->expects($this->once())
            ->method('defaults')
            ->with(ResourceRegistrar::PARAM_RESOURCE_TYPE, $resourceType)
            ->willReturnSelf();

        return $route;
    }
}
