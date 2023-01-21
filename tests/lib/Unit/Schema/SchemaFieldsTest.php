<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Schema;

use CloudCreativity\LaravelJsonApi\Schema\SchemaFields;
use PHPUnit\Framework\TestCase;

class SchemaFieldsTest extends TestCase
{
    /**
     * @return SchemaFields
     */
    public function test(): SchemaFields
    {
        $paths = [
            'a1',
            'a2',
            'a1.b1',
            'a2.b2.c2',
        ];

        $fieldSets = [
            'articles' => 'title,body,a1',
            'people'   => 'name',
        ];

        $fields = new SchemaFields($paths, $fieldSets);

        $this->assertSame(['a1' => 'a1', 'a2' => 'a2'], $fields->getRequestedRelationships(''));
        $this->assertTrue($fields->isRelationshipRequested('', 'a2'));
        $this->assertFalse($fields->isRelationshipRequested('', 'blah'));

        $this->assertSame(['b2' => 'b2'], $fields->getRequestedRelationships('a2'));
        $this->assertSame(['c2' => 'c2'], $fields->getRequestedRelationships('a2.b2'));
        $this->assertTrue($fields->isRelationshipRequested('a2.b2', 'c2'));
        $this->assertFalse($fields->isRelationshipRequested('a2.b2', 'blah'));
        $this->assertEmpty($fields->getRequestedRelationships('a2.b2.c2'));
        $this->assertEmpty($fields->getRequestedRelationships('foo'));

        $this->assertSame([
            'title' => 'title',
            'body' => 'body',
            'a1' => 'a1',
        ], $fields->getRequestedFields('articles'));
        $this->assertNull($fields->getRequestedFields('blah'));
        $this->assertTrue($fields->isFieldRequested('articles', 'title'));
        $this->assertFalse($fields->isFieldRequested('articles', 'blah'));

        return $fields;
    }

    public function test2(): void
    {
        $fields = new SchemaFields([
            'author.phone',
            'comments.createdBy',
        ]);

        $this->assertSame(['author' => 'author', 'comments' => 'comments'], $fields->getRequestedRelationships(''));
        $this->assertSame(['phone' => 'phone'], $fields->getRequestedRelationships('author'));
        $this->assertEmpty($fields->getRequestedRelationships('author.phone'));
        $this->assertSame(['createdBy' => 'createdBy'], $fields->getRequestedRelationships('comments'));
        $this->assertEmpty($fields->getRequestedRelationships('comments.createdBy'));
    }

    /**
     * @param SchemaFields $expected
     * @return void
     * @depends test
     */
    public function testAlreadyParsedParameters(SchemaFields $expected): void
    {
        $paths = [
            'a1',
            'a2',
            'a1.b1',
            'a2.b2.c2',
        ];

        $fieldSets = [
            'articles' => ['title', 'body', 'a1'],
            'people'   => ['name'],
        ];

        $actual = new SchemaFields($paths, $fieldSets);

        $this->assertEquals($expected, $actual);
    }
}
