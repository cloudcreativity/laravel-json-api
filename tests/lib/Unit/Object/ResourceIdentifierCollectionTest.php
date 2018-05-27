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

use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifierCollection;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;

/**
 * Class ResourceIdentifierCollectionTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ResourceIdentifierCollectionTest extends TestCase
{

    /**
     * @var ResourceIdentifier
     */
    private $a;

    /**
     * @var ResourceIdentifier
     */
    private $b;

    protected function setUp()
    {
        $this->a = ResourceIdentifier::create('foo', '123');
        $this->b = ResourceIdentifier::create('bar', '456');
    }

    public function testConstruct()
    {
        $collection = new ResourceIdentifierCollection([$this->a, $this->b]);
        $this->assertSame([$this->a, $this->b], $collection->getAll());

        return $collection;
    }

    /**
     * @depends testConstruct
     */
    public function testIterator(ResourceIdentifierCollection $collection)
    {
        $expected = $collection->getAll();
        $this->assertEquals($expected, iterator_to_array($collection));
    }

    /**
     * @depends testConstruct
     */
    public function testCountable(ResourceIdentifierCollection $collection)
    {
        $this->assertSame(2, count($collection));
    }

    /**
     * @depends testConstruct
     */
    public function testClear(ResourceIdentifierCollection $collection)
    {
        $this->assertSame($collection, $collection->clear());
        $this->assertEmpty($collection->getAll());
    }

    public function testIsEmpty()
    {
        $collection = new ResourceIdentifierCollection();
        $this->assertTrue($collection->isEmpty());
        $collection->add($this->a);
        $this->assertFalse($collection->isEmpty());
    }

    public function testAdd()
    {
        $collection = new ResourceIdentifierCollection();

        $this->assertSame($collection, $collection->add($this->a));
        $collection->add($this->b);
        $this->assertSame([$this->a, $this->b], $collection->getAll());

        return $collection;
    }

    /**
     * @depends testAdd
     */
    public function testAddIgnoresDuplicates(ResourceIdentifierCollection $collection)
    {
        $expected = $collection->getAll();
        $collection->add($this->a)->add($this->b);
        $this->assertEquals($expected, $collection->getAll());
    }

    public function testHas()
    {
        $collection = new ResourceIdentifierCollection([$this->a]);

        $this->assertTrue($collection->has(clone $this->a));
        $this->assertFalse($collection->has($this->b));
    }

    public function testIsComplete()
    {
        $collection = new ResourceIdentifierCollection();

        $this->assertTrue($collection->isComplete());
        $collection->add($this->a);
        $this->assertTrue($collection->isComplete());
        $collection->add(new ResourceIdentifier());
        $this->assertFalse($collection->isComplete());
    }

    public function testIsOnly()
    {
        $collection = new ResourceIdentifierCollection();

        $this->assertTrue($collection->isOnly($this->a->getType()));
        $collection->add($this->a);
        $this->assertTrue($collection->isOnly($this->a->getType()));

        $collection->add($this->b);
        $this->assertFalse($collection->isOnly($this->a->getType()));
        $this->assertFalse($collection->isOnly($this->b->getType()));

        $this->assertTrue($collection->isOnly([
            $this->a->getType(),
            $this->b->getType(),
        ]));
    }

    public function testGetIds()
    {
        $collection = new ResourceIdentifierCollection([$this->a, $this->b]);
        $expected = [$this->a->getId(), $this->b->getId()];

        $this->assertEquals($expected, $collection->getIds());
    }

    public function testMap()
    {
        $collection = new ResourceIdentifierCollection([$this->a, $this->b]);

        $expected = [
            $this->a->getType() => [
                $this->a->getId(),
            ],
            $this->b->getType() => [
                $this->b->getId(),
            ],
        ];

        $this->assertEquals($expected, $collection->map());

        return $collection;
    }

    /**
     * @depends testMap
     */
    public function testMapWithTypeConversion(ResourceIdentifierCollection $collection)
    {
        $a = 'Alias-A';
        $b = 'Alias-B';

        $map = [
            $this->a->getType() => $a,
            $this->b->getType() => $b,
        ];

        $expected = [
            $a => [$this->a->getId()],
            $b => [$this->b->getId()],
        ];

        $this->assertEquals($expected, $collection->map($map));
    }
}
