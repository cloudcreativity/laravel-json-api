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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Schema;

use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use DateTime;
use DateTimeZone;

/**
 * Class SchemaTest
 *
 * @package CloudCreativity\JsonApi
 */
class SchemaTest extends TestCase
{

    /**
     * @var TestSchema
     */
    private $schema;

    /**
     * @return void
     */
    protected function setUp()
    {
        $this->schema = new TestSchema();
    }

    public function testAttributes()
    {
        $expected = ['title' => 'My First Post', 'content' => 'Some content...'];
        $this->assertEquals($expected, $this->schema->getAttributes((object) $expected));
    }

    public function testDasherizedAttributes()
    {
        $expected = [
            'title' => 'My First Blog',
            'blog-synopsis' => 'A synopsis',
            'blog-content' => 'Some content...',
        ];

        $this->assertEquals($expected, $this->schema->getAttributes((object) [
            'title' => 'My First Blog',
            'blog_synopsis' => 'A synopsis',
            'blogContent' => 'Some content...',
        ]));
    }

    public function testNotDasherizedAttributes()
    {
        $expected = [
            'title' => 'My First Blog',
            'blog_synopsis' => 'A synopsis',
            'blog_content' => 'Some content...',
        ];

        $this->schema->dasherize = false;
        $this->assertEquals($expected, $this->schema->getAttributes((object) $expected));
    }

    public function testIgnoresAttributes()
    {
        $expected = [
            'title' => 'My First Post',
            'content' => 'Some content...',
        ];

        $object = $expected;
        $object['views'] = 99;

        $this->schema->attributes = ['title', 'content'];
        $this->assertEquals($expected, $this->schema->getAttributes((object) $object));
    }

    public function testCustomAttributeKey()
    {
        $object = [
            'title' => 'My First Post',
            'content' => 'Some content...',
            'total_views' => 99,
        ];

        $expected = [
            'title' => 'My First Post',
            'content' => 'Some content...',
            'views' => 99,
        ];

        $this->schema->attributes = [
            'title',
            'content',
            'total_views' => 'views',
        ];

        $this->assertEquals($expected, $this->schema->getAttributes((object) $object));
    }

    /**
     * Test date conversion defaults to W3C string and respects `null` as a value
     */
    public function testSerializesDates()
    {
        $object = [
            'title' => 'My First Post',
            'content' => 'Some content...',
            'created_at' => new DateTime('2017-07-01 12:30:00', new DateTimeZone('Europe/London')),
            'updated_at' => new DateTime('2017-07-02 13:00:00', new DateTimeZone('Australia/Melbourne')),
            'deleted_at' => null,
        ];

        $expected = [
            'title' => 'My First Post',
            'content' => 'Some content...',
            'created-at' => '2017-07-01T12:30:00+01:00',
            'updated-at' => '2017-07-02T13:00:00+10:00',
            'deleted-at' => null,
        ];

        $this->assertEquals($expected, $this->schema->getAttributes((object) $object));
    }

    /**
     * Test date conversion with a different date format set on the schema
     */
    public function testSerializesDatesToCustomFormat()
    {
        $object = [
            'title' => 'My First Post',
            'content' => 'Some content...',
            'created_at' => new DateTime('2017-07-01 12:30:00', new DateTimeZone('Europe/London')),
        ];

        $expected = [
            'title' => 'My First Post',
            'content' => 'Some content...',
            'created-at' => '2017-07-01 12:30:00',
        ];

        $this->schema->dateFormat = 'Y-m-d H:i:s';
        $this->assertEquals($expected, $this->schema->getAttributes((object) $object));
    }

    /**
     * Test that if there is a specific method for serializing an attribute value, it is used.
     */
    public function testAttributeSerializer()
    {
        $object = [
            'title' => 'My First Post',
            'content' => 'Some content...',
            'foo' => 'foobar',
        ];

        $expected = $object;
        $expected['foo'] = 'FOOBAR';

        $this->assertEquals($expected, $this->schema->getAttributes((object) $object));
    }
}
