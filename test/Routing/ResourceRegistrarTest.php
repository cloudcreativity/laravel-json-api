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

use CloudCreativity\LaravelJsonApi\TestCase;
use Illuminate\Contracts\Routing\Registrar;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Class ResourceRegistrarTest
 * @package CloudCreativity\LaravelJsonApi
 */
final class ResourceRegistrarTest extends TestCase
{

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
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
        $this->willSee('GET', '/posts', 'index', 'posts.index');
        $this->willSee('POST', '/posts', 'create', 'posts.create');
        $this->willSee('GET', '/posts/{resource_id}', 'read', 'posts.read');
        $this->willSee('PATCH', '/posts/{resource_id}', 'update', 'posts.update');
        $this->willSee('DELETE', '/posts/{resource_id}', 'delete', 'posts.delete');
        $this->willSee('GET', '/posts/{resource_id}/{relationship_name}', 'readRelatedResource', 'posts.related');
        $this->willSee('GET', '/posts/{resource_id}/relationships/{relationship_name}', 'readRelationship', 'posts.relationship.read');
        $this->willSee('PATCH', '/posts/{resource_id}/relationships/{relationship_name}', 'replaceRelationship', 'posts.relationship.replace');
        $this->willSee('POST', '/posts/{resource_id}/relationships/{relationship_name}', 'addToRelationship', 'posts.relationship.add');
        $this->willSee('DELETE', '/posts/{resource_id}/relationships/{relationship_name}', 'removeFromRelationship', 'posts.relationship.remove');

        $this->registrar->resource('posts', 'PostsController');
    }

    /**
     * @param $uri
     * @param $httpMethod
     * @param $controllerMethod
     */
    private function willSee(
        $httpMethod,
        $uri,
        $controllerMethod,
        $as
    ) {
        $this->mock
           ->expects($this->at($this->index))
           ->method(strtolower($httpMethod))
           ->with($uri, [
               'uses' => 'PostsController@' . $controllerMethod,
               'as' => $as,
           ]);

        $this->index++;
    }
}
