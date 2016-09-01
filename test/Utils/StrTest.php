<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Utils;

use CloudCreativity\LaravelJsonApi\TestCase;

/**
 * Class StrTest
 * @package CloudCreativity\LaravelJsonApi
 */
final class StrTest extends TestCase
{

    public function testDasherize()
    {
        $values = [
            'foo' => 'foo',
            'foo_bar' => 'foo-bar',
            'fooBar' => 'foo-bar',
        ];

        foreach ($values as $value => $expected) {
            $actual = Str::dasherize($value);
            $this->assertSame($expected, $actual, "Did not dasherize '$value' correctly");
        }
    }

    public function testCamel()
    {
        $values = [
            'foo' => 'foo',
            'foo-bar' => 'fooBar',
            'fooBar' => 'fooBar',
            'foo_bar' => 'fooBar',
        ];

        foreach ($values as $value => $expected) {
            $actual = Str::camel($value);
            $this->assertSame($expected, $actual, "Did not camel case '$value' correctly");
        }
    }

    public function testSnake()
    {
        $values = [
            'foo' => 'foo',
            'foo-bar' => 'foo_bar',
            'fooBar' => 'foo_bar',
            'foo_bar' => 'foo_bar',
        ];

        foreach ($values as $value => $expected) {
            $actual = Str::snake($value);
            $this->assertSame($expected, $actual, "Did not snake case '$value' correctly");
        }
    }
}
