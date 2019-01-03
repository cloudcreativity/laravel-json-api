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

use CloudCreativity\LaravelJsonApi\Object\Relationship;
use CloudCreativity\LaravelJsonApi\Object\Relationships;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use stdClass;

/**
 * Class RelationshipsTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class RelationshipsTest extends TestCase
{

    const KEY_A = 'foo';
    const KEY_B = 'bar';

    /**
     * @var stdClass
     */
    private $data;

    protected function setUp()
    {
        $belongsTo = new stdClass();
        $belongsTo->{ResourceIdentifier::TYPE} = 'foo';
        $belongsTo->{ResourceIdentifier::ID} = 123;

        $a = new stdClass();
        $a->{Relationship::DATA} = $belongsTo;

        $b = new stdClass();
        $b->{Relationship::DATA} = null;

        $this->data = new stdClass();
        $this->data->{self::KEY_A} = $a;
        $this->data->{self::KEY_B} = $b;
    }

    public function testGet()
    {
        $object = new Relationships($this->data);
        $a = new Relationship($this->data->{self::KEY_A});
        $b = new Relationship($this->data->{self::KEY_B});

        $this->assertEquals($a, $object->getRelationship(self::KEY_A));
        $this->assertEquals($b, $object->getRelationship(self::KEY_B));

        return $object;
    }

    /**
     * @depends testGet
     */
    public function testAll(Relationships $object)
    {
        $expected = [
            self::KEY_A => $object->getRelationship(self::KEY_A),
            self::KEY_B => $object->getRelationship(self::KEY_B),
        ];

        $this->assertEquals($expected, iterator_to_array($object->getAll()));
    }

}
