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

use CloudCreativity\LaravelJsonApi\Object\Relationship;
use CloudCreativity\LaravelJsonApi\Object\Relationships;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Object\ResourceObject;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use CloudCreativity\Utils\Object\StandardObject;

/**
 * Class ResourceTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceObjectTest extends TestCase
{

    /**
     * @var object
     */
    private $data;

    /**
     * @var ResourceObject
     */
    private $object;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        $this->data = (object) [
            'type' => 'foo',
            'id' => '123',
            'attributes' => (object) [
                'foo' => 'bar',
            ],
            'relationships' => (object) [
                'baz' => (object) [
                    'data' => null,
                ],
            ],
            'meta' => (object) [
                'bat' => 'foobar',
            ],
        ];

        $this->object = new ResourceObject($this->data);
    }

    public function testGetType()
    {
        $this->assertSame('foo', $this->object->getType());
    }

    public function testGetId()
    {
        $this->assertSame('123', $this->object->getId());
    }

    public function testHasId()
    {
        $this->assertTrue($this->object->hasId());
        unset($this->data->id);
        $this->assertFalse($this->object->hasId());
    }

    public function testGetIdentifier()
    {
        $expected = ResourceIdentifier::create('foo', '123');
        $this->assertEquals($expected, $this->object->getIdentifier());
    }

    public function testGetAttributes()
    {
        $expected = new StandardObject($this->data->attributes);

        $this->assertEquals($expected, $this->object->getAttributes());
    }

    public function testGetEmptyAttributes()
    {
        unset($this->data->attributes);
        $this->assertEquals(new StandardObject(), $this->object->getAttributes());
    }

    public function testHasAttributes()
    {
        $this->assertTrue($this->object->hasAttributes());
        unset($this->data->attributes);
        $this->assertFalse($this->object->hasAttributes());
    }

    public function testGetRelationships()
    {
        $expected = new Relationships($this->data->relationships);

        $this->assertEquals($expected, $this->object->getRelationships());
    }

    public function testHasRelationships()
    {
        $this->assertTrue($this->object->hasRelationships());
        unset($this->data->relationships);
        $this->assertFalse($this->object->hasRelationships());
    }

    public function testGetRelationship()
    {
        $expected = new Relationship($this->data->relationships->baz);

        $this->assertEquals($expected, $this->object->getRelationship('baz'));
        $this->assertNull($this->object->getRelationship('bat'));
    }

    public function getMeta()
    {
        $expected = new StandardObject($this->data->meta);
        $this->assertEquals($expected, $this->object->getMeta());
    }

    public function testHasMeta()
    {
        $this->assertTrue($this->object->hasMeta());
        unset($this->data->meta);
        $this->assertFalse($this->object->hasMeta());
    }
}
