<?php

/**
 * Copyright 2019 Cloud Creativity Limited
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
use CloudCreativity\LaravelJsonApi\Object\Relationship;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifierCollection;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use CloudCreativity\Utils\Object\StandardObject;
use stdClass;

/**
 * Class RelationshipTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class RelationshipTest extends TestCase
{

    protected $belongsTo;
    protected $hasMany;

    protected function setUp(): void
    {
        $this->belongsTo = new stdClass();
        $this->belongsTo->{ResourceIdentifier::TYPE} = 'foo';
        $this->belongsTo->{ResourceIdentifier::ID} = 123;

        $a = new stdClass();
        $a->{ResourceIdentifier::TYPE} = 'bar';
        $a->{ResourceIdentifier::ID} = 456;

        $b = new stdClass();
        $b->{ResourceIdentifier::TYPE} = 'baz';
        $b->{ResourceIdentifier::ID} = 789;

        $this->hasMany = [$a, $b];
    }

    public function testHasOne()
    {
        $input = new stdClass();
        $input->{Relationship::DATA} = $this->belongsTo;

        $object = new Relationship($input);
        $expected = new ResourceIdentifier($this->belongsTo);

        $this->assertEquals($expected, $object->getData());
        $this->assertEquals($expected, $object->getIdentifier());
        $this->assertTrue($object->isHasOne());
        $this->assertFalse($object->isHasMany());
        $this->assertTrue($object->hasIdentifier());
    }

    public function testEmptyHasOne()
    {
        $input = new stdClass();
        $input->{Relationship::DATA} = null;

        $object = new Relationship($input);

        $this->assertNull($object->getData());
        $this->assertTrue($object->isHasOne());
        $this->assertFalse($object->isHasMany());
        $this->assertFalse($object->hasIdentifier());

        $this->expectException(RuntimeException::class);
        $object->getIdentifier();
    }

    public function testHasMany()
    {
        $input = new stdClass();
        $input->{Relationship::DATA} = $this->hasMany;

        $object = new Relationship($input);
        $expected = ResourceIdentifierCollection::create($this->hasMany);

        $this->assertEquals($expected, $object->getData());
        $this->assertTrue($object->isHasMany());
        $this->assertFalse($object->isHasOne());
    }

    public function testEmptyHasMany()
    {
        $input = new stdClass();
        $input->{Relationship::DATA} = [];

        $object = new Relationship($input);

        $this->assertEquals(new ResourceIdentifierCollection(), $object->getData());
        $this->assertTrue($object->isHasMany());
        $this->assertFalse($object->isHasOne());
    }

    public function testGetMeta()
    {
        $object = new Relationship();

        $this->assertFalse($object->hasMeta());
        $this->assertEquals(new StandardObject(), $object->getMeta());

        $input = new stdClass();
        $input->meta = new stdClass();
        $input->meta->foo = 'bar';

        $object = new Relationship($input);

        $this->assertTrue($object->hasMeta());
        $this->assertEquals(new StandardObject($input->meta), $object->getMeta());
    }
}
