<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Http\Requests;

use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\JsonApi\Http\Requests\InboundRequest;
use CloudCreativity\JsonApi\Object\Document;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

/**
 * Class RequestTest
 *
 * @package CloudCreativity\JsonApi
 */
class InboundRequestTest extends TestCase
{

    /**
     * @var InboundRequest
     */
    private $request;

    public function testIsIndex()
    {
        $this->willSee('GET')
            ->assertRequestType('index');

        $this->assertSame('posts', $this->request->getResourceType());
        $this->assertNull($this->request->getResourceId());
        $this->assertNull($this->request->getResourceIdentifier());
        $this->assertNull($this->request->getRelationshipName());
        $this->assertFalse($this->request->hasRelationships());
        $this->assertNull($this->request->getDocument());
        $this->assertEquals($this->factory->createQueryParameters(), $this->request->getParameters());
    }

    public function testIsCreateResource()
    {
        $this->willSee('POST')
            ->assertRequestType('createResource');
    }

    public function testIsReadResource()
    {
        $this->willSee('GET', '1')
            ->assertRequestType('readResource');

        $this->assertSame('1', $this->request->getResourceId());
        $this->assertInstanceOf(
            ResourceIdentifierInterface::class,
            $identifier = $this->request->getResourceIdentifier()
        );
        $this->assertSame('posts', $identifier->getType());
        $this->assertSame('1', $identifier->getId());
    }

    public function testIsUpdateResource()
    {
        $this->willSee('PATCH', '1')
            ->assertRequestType('updateResource');
    }

    public function testIsDeleteResource()
    {
        $this->willSee('DELETE', '1')
            ->assertRequestType('deleteResource');
    }

    public function testIsReadRelatedResource()
    {
        $this->willSee('GET', '1', 'comments')
            ->assertRequestType('readRelatedResource');
    }

    public function testIsReadRelationship()
    {
        $this->willSee('GET', '1', 'comments', true)
            ->assertRequestType('readRelationship');

        $this->assertTrue($this->request->hasRelationships());
    }

    public function testIsReplaceRelationship()
    {
        $this->willSee('PATCH', '1', 'comments', true)
            ->assertRequestType('replaceRelationship');
    }

    public function testIsAddToRelationship()
    {
        $this->willSee('POST', '1', 'comments', true)
            ->assertRequestType('addToRelationship');
    }

    public function testIsRemoveFromRelationship()
    {
        $this->willSee('DELETE', '1', 'comments', true)
            ->assertRequestType('removeFromRelationship');
    }

    public function testDocumentAndParameters()
    {
        $document = new Document((object) [
            'data' => (object) [
                'type' => 'posts',
                'attributes' => (object) [
                    'title' => 'My First Post',
                ],
            ],
        ]);

        $request = $this->factory->createInboundRequest(
            'POST',
            'posts',
            '1',
            null,
            false,
            $document,
            $params = new EncodingParameters()
        );

        $this->assertEquals($document, $request->getDocument());
        $this->assertNotSame($document, $request->getDocument());
        $this->assertSame($params, $request->getParameters());
    }

    /**
     * @param $requestType
     * @return $this
     */
    private function assertRequestType($requestType)
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
            $actual = call_user_func([$this->request, $method]);
            $this->assertSame($expected, $actual, $message);
        }

        return $this;
    }

    /**
     * @param string $method
     * @param string|null $resourceId
     * @param string|null $relationshipName
     * @param bool $relationships
     * @return $this
     */
    private function willSee($method, $resourceId = null, $relationshipName = null, $relationships = false)
    {
        $this->request = $this->factory->createInboundRequest(
           $method,
           'posts',
           $resourceId,
           $relationshipName,
           $relationships
        );

        return $this;
    }

}
