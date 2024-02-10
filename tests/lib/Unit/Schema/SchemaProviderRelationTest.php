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

use CloudCreativity\LaravelJsonApi\Contracts\Schema\SchemaProviderInterface;
use CloudCreativity\LaravelJsonApi\Schema\SchemaProviderRelation;
use Neomerx\JsonApi\Contracts\Schema\SchemaInterface;
use PHPUnit\Framework\TestCase;

class SchemaProviderRelationTest extends TestCase
{
    /**
     * @return array
     */
    public static function dataProvider(): array
    {
        return [
            [null],
            [(object) ['foo' => 'bar']],
            [[(object) ['foo' => 'bar'], (object) ['baz' => 'bat']]],
        ];
    }

    /**
     * @param mixed $expected
     * @dataProvider dataProvider
     */
    public function testShowData($expected): void
    {
        $relation = new SchemaProviderRelation('posts', 'author', [
            SchemaProviderInterface::SHOW_DATA => true,
            SchemaProviderInterface::DATA => $expected,
        ]);

        $this->assertTrue($relation->showData());
        $this->assertSame($expected, $relation->data());
        $this->assertSame([SchemaInterface::RELATIONSHIP_DATA => $expected], $relation->parse());
    }

    public function testDoNotShowData(): void
    {
        $relation = new SchemaProviderRelation('posts', 'author', [
            SchemaProviderInterface::SHOW_DATA => false,
            SchemaProviderInterface::DATA => $expected = (object) ['foo' => 'bar'],
        ]);

        $this->assertFalse($relation->showData());
        $this->assertSame($expected, $relation->data());
        $this->assertEmpty($relation->parse());
    }

    /**
     * @param mixed $expected
     * @dataProvider dataProvider
     */
    public function testShowDataNotSpecifiedWithData($expected): void
    {
        $relation = new SchemaProviderRelation('posts', 'author', [
            SchemaProviderInterface::DATA => $expected,
        ]);

        $this->assertTrue($relation->showData());
        $this->assertSame($expected, $relation->data());
        $this->assertSame([SchemaInterface::RELATIONSHIP_DATA => $expected], $relation->parse());
    }

    public function testShowDataNotSpecifiedWithoutData(): void
    {
        $relation = new SchemaProviderRelation('posts', 'author', []);

        $this->assertFalse($relation->showData());
        $this->assertNull($relation->data());
        $this->assertEmpty($relation->parse());
    }

    public function testInvalidShowData(): void
    {
        $relation = new SchemaProviderRelation('posts', 'tags', [
            SchemaProviderInterface::SHOW_DATA => 'blah',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Show data on resource "posts" relation "tags" must be a boolean.');

        $relation->showData();
    }

    /**
     * @return array
     */
    public static function metaProvider(): array
    {
        return [
            [['foo' => 'bar']],
            [(object) ['foo' => 'bar']],
            [fn() => ['foo' => 'bar']],
        ];
    }

    /**
     * @param $expected
     * @dataProvider metaProvider
     */
    public function testMeta($expected): void
    {
        $relation = new SchemaProviderRelation('posts', 'author', [
            SchemaProviderInterface::META => $expected,
        ]);

        $this->assertTrue($relation->hasMeta());
        $this->assertSame($expected, $relation->meta());
        $this->assertSame([SchemaInterface::RELATIONSHIP_META => $expected], $relation->parse());
    }

    /**
     * @return array
     */
    public static function emptyMetaProvider(): array
    {
        return [
            [null],
            [[]],
        ];
    }

    /**
     * @param mixed $value
     * @dataProvider emptyMetaProvider
     */
    public function testEmptyMeta($value): void
    {
        $relation = new SchemaProviderRelation('posts', 'author', [
            SchemaProviderInterface::META => $value,
        ]);

        $this->assertFalse($relation->hasMeta());
        $this->assertSame($value, $relation->meta());
        $this->assertEmpty($relation->parse());
    }

    /**
     * @return array
     */
    public static function booleanProvider(): array
    {
        return [
            [true],
            [false],
        ];
    }

    /**
     * @param bool $expected
     * @dataProvider booleanProvider
     */
    public function testShowSelfLink(bool $expected): void
    {
        $relation = new SchemaProviderRelation('posts', 'tags', [
            SchemaProviderInterface::SHOW_SELF => $expected,
        ]);

        $this->assertSame($expected, $relation->showSelfLink());
        $this->assertSame([SchemaInterface::RELATIONSHIP_LINKS_SELF => $expected], $relation->parse());
    }

    public function testInvalidShowSelf(): void
    {
        $relation = new SchemaProviderRelation('posts', 'tags', [
            SchemaProviderInterface::SHOW_SELF => 'blah',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Show self link on resource "posts" relation "tags" must be a boolean.');

        $relation->showSelfLink();
    }

    /**
     * @param bool $expected
     * @dataProvider booleanProvider
     */
    public function testShowRelatedLink(bool $expected): void
    {
        $relation = new SchemaProviderRelation('posts', 'tags', [
            SchemaProviderInterface::SHOW_RELATED => $expected,
        ]);

        $this->assertSame($expected, $relation->showRelatedLink());
        $this->assertSame([SchemaInterface::RELATIONSHIP_LINKS_RELATED => $expected], $relation->parse());
    }

    public function testInvalidShowRelated(): void
    {
        $relation = new SchemaProviderRelation('posts', 'tags', [
            SchemaProviderInterface::SHOW_RELATED => 'blah',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Show related link on resource "posts" relation "tags" must be a boolean.');

        $relation->showRelatedLink();
    }

    public function testLinks(): void
    {
        $this->markTestIncomplete('@TODO');
    }
}