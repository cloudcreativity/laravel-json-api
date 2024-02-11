<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Validation\Rules;

use CloudCreativity\LaravelJsonApi\Rules\HasMany;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;

class HasManyTest extends TestCase
{

    /**
     * @return array
     */
    public static function validProvider(): array
    {
        return [
            'empty' => [
                'users',
                [],
            ],
            'identifier' => [
                'users',
                [
                    ['type' => 'users', 'id' => '123'],
                ],
            ],
            'identifiers' => [
                'users',
                [
                    ['type' => 'users', 'id' => '123'],
                    ['type' => 'users', 'id' => '456'],
                ],
            ],
            'polymorph identifier' => [
                ['users', 'people'],
                [
                    ['type' => 'people', 'id' => '123'],
                ],
            ],
            'polymorph identifiers' => [
                ['users', 'people'],
                [
                    ['type' => 'people', 'id' => '123'],
                    ['type' => 'users', 'id' => '456'],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function invalidProvider(): array
    {
        return [
            'has-one null' => [
                'users',
                ['data' => null],
            ],
            'has-one identifier' => [
                'users',
                ['data' => ['type' => 'users', 'id' => '123']],
            ],
            'identifiers' => [
                'users',
                [
                    ['type' => 'users', 'id' => '123'],
                    ['type' => 'people', 'id' => '456'],
                ],
            ],
            'polymorph identifiers' => [
                ['users', 'people'],
                [
                    ['type' => 'people', 'id' => '123'],
                    ['type' => 'users', 'id' => '456'],
                    ['type' => 'foobars', 'id' => '789'],
                ],
            ],
        ];
    }

    /**
     * @param $types
     * @param $value
     * @dataProvider validProvider
     */
    public function testValid($types, $value): void
    {
        $types = (array) $types;
        $rule = new HasMany(...$types);

        $this->assertTrue($rule->passes('authors', $value));
    }

    public function testValidWithAttributeName(): void
    {
        $rule = new HasMany();

        $this->assertTrue($rule->passes('tags', [
            ['type' => 'tags', 'id' => '1'],
        ]));

        $this->assertFalse($rule->passes('tags', [
            ['type' => 'users', 'id' => '1'],
        ]));
    }

    /**
     * @param $types
     * @param $value
     * @dataProvider invalidProvider
     */
    public function testInvalid($types, $value): void
    {
        $types = (array) $types;
        $rule = new HasMany(...$types);

        $this->assertFalse($rule->passes('authors', $value));
    }

}
