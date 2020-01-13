<?php

/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Object;

use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectCollectionInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Object\Document;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifierCollection;
use CloudCreativity\LaravelJsonApi\Object\ResourceObject as ResourceObject;
use CloudCreativity\LaravelJsonApi\Object\ResourceObjectCollection;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use stdClass;

/**
 * Class ResourceCollectionTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceObjectCollectionTest extends TestCase
{

    public function testCreate()
    {
        $document = <<<JSON_API
{
    "data": [
        {
            "type": "posts",
            "id": "123",
            "attributes": {
                "title": "My First Post"
            }
        },
        {
            "type": "posts",
            "id": "456",
            "attributes": {
                "title": "My Last Post"
            }
        }
    ]
}
JSON_API;

        $document = new Document(json_decode($document));
        $resources = $document->getResources();

        $this->assertInstanceOf(ResourceObjectCollectionInterface::class, $resources);

        return $resources;
    }

    /**
     * @param ResourceObjectCollection $resources
     * @depends testCreate
     */
    public function testHas(ResourceObjectCollection $resources)
    {
        $this->assertTrue($resources->has(ResourceIdentifier::create('posts', '456')));
        $this->assertFalse($resources->has(ResourceIdentifier::create('comments', '456')));
    }

    /**
     * @param ResourceObjectCollection $resources
     * @depends testCreate
     */
    public function testGet(ResourceObjectCollection $resources)
    {
        $this->assertEquals($this->resourceA(), $resources->get(ResourceIdentifier::create('posts', '123')));
    }

    /**
     * @param ResourceObjectCollection $resources
     * @depends testCreate
     */
    public function testGetMissingResource(ResourceObjectCollection $resources)
    {
        $this->expectException(RuntimeException::class);
        $resources->get(ResourceIdentifier::create('posts', '999'));
    }

    /**
     * @param ResourceObjectCollection $resources
     * @depends testCreate
     */
    public function testAllAndIterator(ResourceObjectCollection $resources)
    {
        $expected = [$this->resourceA(), $this->resourceB()];
        $this->assertEquals($expected, $resources->getAll());
        $this->assertEquals($expected, iterator_to_array($resources));
    }

    /**
     * @param ResourceObjectCollection $resources
     * @depends testCreate
     */
    public function testCount(ResourceObjectCollection $resources)
    {
        $this->assertEquals(2, count($resources));
    }

    /**
     * @param ResourceObjectCollection $resources
     * @depends testCreate
     */
    public function testIsEmpty(ResourceObjectCollection $resources)
    {
        $this->assertFalse($resources->isEmpty());
        $this->assertTrue((new ResourceObjectCollection())->isEmpty());
    }

    /**
     * @param ResourceObjectCollection $resources
     * @depends testCreate
     */
    public function testGetIds(ResourceObjectCollection $resources)
    {
        $expected = [$this->resourceA()->getIdentifier(), $this->resourceB()->getIdentifier()];

        $this->assertEquals(new ResourceIdentifierCollection($expected), $resources->getIdentifiers());
    }

    /**
     * @return ResourceObject
     */
    private function resourceA()
    {
        $resource = new stdClass();
        $resource->type = 'posts';
        $resource->id = '123';
        $resource->attributes = new stdClass();
        $resource->attributes->title = 'My First Post';

        return new ResourceObject($resource);
    }

    /**
     * @return ResourceObject
     */
    private function resourceB()
    {
        $resource = new stdClass();
        $resource->type = 'posts';
        $resource->id = '456';
        $resource->attributes = new stdClass();
        $resource->attributes->title = 'My Last Post';

        return new ResourceObject($resource);
    }
}
