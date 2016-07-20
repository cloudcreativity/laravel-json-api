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

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use Closure;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use CloudCreativity\LaravelJsonApi\TestCase;
use Illuminate\Http\Request as HttpRequest;
use Illuminate\Routing\Route;

/**
 * Class RequestTest
 * @package CloudCreativity\LaravelJsonApi
 */
final class RequestTest extends TestCase
{

    public function testIsIndex()
    {
        $request = $this->request($this->httpRequest('/posts'));
        $this->assertRequestType($request, 'index');
    }

    public function testIsCreateResource()
    {
        $request = $this->request($this->httpRequest('/posts', null, null, 'POST'));
        $this->assertRequestType($request, 'createResource');
    }

    public function testIsReadResource()
    {
        $request = $this->request($this->httpRequest('/posts/1', '1'));
        $this->assertRequestType($request, 'readResource');
    }

    public function testIsUpdateResource()
    {
        $request = $this->request($this->httpRequest('/posts/1', '1', null, 'PATCH'));
        $this->assertRequestType($request, 'updateResource');
    }

    public function testIsDeleteResource()
    {
        $request = $this->request($this->httpRequest('/posts/1', '1', null, 'DELETE'));
        $this->assertRequestType($request, 'deleteResource');
    }

    public function testIsReadRelatedResource()
    {
        $request = $this->request($this->httpRequest('/posts/1/comments', '1', 'comments'));
        $this->assertRequestType($request, 'readRelatedResource');
    }

    public function testIsReadRelationship()
    {
        $request = $this->request($this->httpRequest('/posts/1/relationships/comments', '1', 'comments'));
        $this->assertRequestType($request, 'readRelationship');
    }

    public function testIsReplaceRelationship()
    {
        $request = $this->request($this->httpRequest('/posts/1/relationships/comments', '1', 'comments', 'PATCH'));
        $this->assertRequestType($request, 'replaceRelationship');
    }

    public function testIsAddToRelationship()
    {
        $request = $this->request($this->httpRequest('/posts/1/relationships/comments', '1', 'comments', 'POST'));
        $this->assertRequestType($request, 'addToRelationship');
    }

    public function testIsRemoveFromRelationship()
    {
        $request = $this->request($this->httpRequest('/posts/1/relationships/comments', '1', 'comments', 'DELETE'));
        $this->assertRequestType($request, 'removeFromRelationship');
    }

    /**
     * @param AbstractRequest $request
     * @param $requestType
     */
    private function assertRequestType(AbstractRequest $request, $requestType)
    {
        $checker = 'is' . ucfirst($requestType);

        $methods = [
            'isIndex',
            'isCreateResource',
            'isReadResource',
            'isUpdateResource',
            'isDeleteResource',
            'isReadRelatedResource',
            'isReadRelationship',
            'isReplaceRelationship',
            'isAddToRelationship',
            'isRemoveFromRelationship',
        ];

        foreach ($methods as $method) {
            $message = sprintf('Calling %s for %s', $method, $requestType);
            $expected = ($checker === $method);
            $actual = call_user_func([$request, $method]);
            $this->assertSame($expected, $actual, $message);
        }
    }

    /**
     * @param HttpRequest $httpRequest
     * @return AbstractRequest
     */
    private function request(HttpRequest $httpRequest)
    {
        $mock = $this->getMockForAbstractClass(AbstractRequest::class);
        $mock->method('resourceType')->willReturn('posts');
        app()->instance(HttpRequest::class, $httpRequest);

        return $mock;
    }

    /**
     * @param $uri
     * @param null $resourceId
     * @param null $relationshipName
     * @param string $method
     * @param array $parameters
     * @param null $content
     * @return HttpRequest
     */
    private function httpRequest(
        $uri,
        $resourceId = null,
        $relationshipName = null,
        $method = 'GET',
        array $parameters = [],
        $content = null
    ) {
        $request = HttpRequest::createFromBase(HttpRequest::create(
            'http://localhost' . $uri,
            $method,
            $parameters,
            [],
            [],
            [],
            $content
        ));

        $request->setRouteResolver($this->routeResolver($resourceId, $relationshipName));

        return $request;
    }

    /**
     * @param $resourceId
     * @param $relationshipName
     * @return Closure
     */
    private function routeResolver($resourceId, $relationshipName)
    {
        $mock = $this->getMockBuilder(Route::class)->disableOriginalConstructor()->getMock();
        $mock->method('parameter')->willReturnMap([
            [ResourceRegistrar::PARAM_RESOURCE_ID, null, $resourceId],
            [ResourceRegistrar::PARAM_RELATIONSHIP_NAME, null, $relationshipName],
        ]);

        return function () use ($mock) {
            return $mock;
        };
    }
}
