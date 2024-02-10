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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Validation\Rules;

use CloudCreativity\LaravelJsonApi\Rules\HasOne;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;

class HasOneTest extends TestCase
{

    /**
     * @return array
     */
    public static function validProvider(): array
    {
        return [
            'null' => [
                'users',
                null,
            ],
            'identifier' => [
                'users',
                ['type' => 'users', 'id' => '123'],
            ],
            'polymorph null' => [
                ['users', 'people'],
                null,
            ],
            'polymorph identifier 1' => [
                ['users', 'people'],
                ['type' => 'users', 'id' => '123'],
            ],
            'polymorph identifier 2' => [
                ['users', 'people'],
                ['type' => 'people', 'id' => '456'],
            ],
        ];
    }

    /**
     * @return array
     */
    public static function invalidProvider(): array
    {
        return [
            'empty has-many' => [
                'users',
                [],
            ],
            'has-many' => [
                'users',
                [
                    ['type' => 'users', 'id' => '123'],
                ],
            ],
            'invalid type' => [
                'users',
                ['type' => 'people', 'id' => '456'],
            ],
            'invalid polymorph type' => [
                ['users', 'people'],
                ['type' => 'foobar', 'id' => '1'],
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
        $rule = new HasOne(...$types);

        $this->assertTrue($rule->passes('author', $value));
    }

    public function testValidWithAttributeName(): void
    {
        $rule = new HasOne();

        $this->assertTrue($rule->passes('author', ['type' => 'authors', 'id' => '1']));
        $this->assertFalse($rule->passes('author', ['type' => 'users', 'id' => '1']));
    }

    /**
     * @param $types
     * @param $value
     * @dataProvider invalidProvider
     */
    public function testInvalid($types, $value): void
    {
        $types = (array) $types;
        $rule = new HasOne(...$types);

        $this->assertFalse($rule->passes('author', $value));
    }
}
