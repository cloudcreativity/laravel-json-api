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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Utils;

use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use CloudCreativity\LaravelJsonApi\Utils\Str;

/**
 * Class StrTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class StrTest extends TestCase
{

    /**
     * @return array
     */
    public static function dasherizeProvider()
    {
        return [
            ['foo', 'foo'],
            ['foo_bar', 'foo-bar'],
            ['fooBar', 'foo-bar'],
            ['foo-bar', 'foo-bar'],
        ];
    }

    /**
     * @param $value
     * @param $expected
     * @dataProvider dasherizeProvider
     */
    public function testDasherize($value, $expected)
    {
        $this->assertSame($expected, Str::dasherize($value));
    }

    /**
     * @return array
     */
    public static function decamelizeProvider()
    {
        return [
            ['foo', 'foo'],
            ['fooBar', 'foo_bar'],
            ['fooBarBazBat', 'foo_bar_baz_bat'],
            ['foo_bar', 'foo_bar'],
        ];
    }

    /**
     * @param $value
     * @param $expected
     * @dataProvider decamelizeProvider
     */
    public function testDecamelize($value, $expected)
    {
        $this->assertSame($expected, Str::decamelize($value));
    }

    /**
     * @return array
     */
    public static function underscoreProvider()
    {
        return [
            ['foo', 'foo'],
            ['fooBar', 'foo_bar'],
            ['fooBarBazBat', 'foo_bar_baz_bat'],
            ['foo_bar', 'foo_bar'],
            ['foo-bar', 'foo_bar'],
            ['foo-bar-baz-bat', 'foo_bar_baz_bat'],
        ];
    }

    /**
     * @param $value
     * @param $expected
     * @dataProvider underscoreProvider
     */
    public function testUnderscore($value, $expected)
    {
        $this->assertSame($expected, Str::underscore($value));
    }

    /**
     * @return array
     */
    public static function camelizeProvider()
    {
        return [
            ['foo', 'foo'],
            ['foo-bar', 'fooBar'],
            ['foo_bar', 'fooBar'],
            ['foo_bar_baz_bat', 'fooBarBazBat'],
            ['fooBar', 'fooBar'],
        ];
    }

    /**
     * @param $value
     * @param $expected
     * @dataProvider camelizeProvider
     */
    public function testCamelizeAndClassify($value, $expected)
    {
        $this->assertSame($expected, Str::camelize($value), 'camelize');
        $this->assertSame(ucfirst($expected), Str::classify($value), 'classify');
    }
}
