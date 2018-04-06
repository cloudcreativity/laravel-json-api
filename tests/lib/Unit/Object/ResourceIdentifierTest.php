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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Object;

use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use CloudCreativity\Utils\Object\StandardObject;
use stdClass;

/**
 * Class ResourceIdentifierTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceIdentifierTest extends TestCase
{

    public function testTypeAndId()
    {
        $identifier = new ResourceIdentifier();
        $this->assertFalse($identifier->hasType());
        $this->assertFalse($identifier->hasId());

        $identifier = ResourceIdentifier::create('posts', '1');

        $this->assertSame('posts', $identifier->getType());
        $this->assertTrue($identifier->hasType());

        $this->assertSame('1', $identifier->getId());
        $this->assertTrue($identifier->hasId());

        return $identifier;
    }

    /**
     * @depends testTypeAndId
     */
    public function testIsType(ResourceIdentifier $identifier)
    {
        $this->assertTrue($identifier->isType('posts'));
        $this->assertFalse($identifier->isType('invalid-type'));
        $this->assertTrue($identifier->isType(['not-a-match', 'posts']));
    }

    public function testIsComplete()
    {
        $this->assertFalse((new ResourceIdentifier())->isComplete());

        $complete = ResourceIdentifier::create('posts', '1');

        $this->assertTrue($complete->isComplete());
    }

    public function testMapType()
    {
        $identifier = ResourceIdentifier::create('posts', '1');
        $expected = 'My\Class';

        $map = [
            'not-a-match' => 'unexpected',
            'posts' => $expected,
        ];

        $this->assertSame($expected, $identifier->mapType($map));

        $this->expectException(RuntimeException::class);
        $identifier->mapType(['not-a-match' => 'unexpected']);
    }

    public function testMeta()
    {
        $identifier = new ResourceIdentifier();

        $this->assertEquals(new StandardObject(), $identifier->getMeta());

        $meta = new stdClass();
        $meta->foo = 'bar';
        $expected = new StandardObject($meta);

        $identifier->set(ResourceIdentifier::META, $meta);

        $this->assertEquals($expected, $identifier->getMeta());
    }

    /**
     * @return array
     */
    public function invalidTypeProvider()
    {
        return [
            'null' => [null],
            'integer' => [1],
            'object' => [new StandardObject()],
            'array' => [[]],
            'empty string' => [''],
        ];
    }

    /**
     * @param $type
     * @dataProvider invalidTypeProvider
     */
    public function testInvalidType($type)
    {
        $this->expectException(RuntimeException::class);
        ResourceIdentifier::create($type, '1')->getType();
    }

    /**
     * @return array
     */
    public function invalidIdProvider()
    {
        return [
            'null' => [null],
            'integer' => [1],
            'object' => [new StandardObject()],
            'array' => [[]],
            'empty string' => [''],
        ];
    }

    /**
     * @param $id
     * @dataProvider invalidIdProvider
     */
    public function testInvalidId($id)
    {
        $this->expectException(RuntimeException::class);
        ResourceIdentifier::create('posts', $id)->getId();
    }
}
