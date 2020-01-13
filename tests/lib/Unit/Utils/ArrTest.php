<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Utils\Arr;
use PHPUnit\Framework\TestCase;

class ArrTest extends TestCase
{

    public function testCamelize()
    {
        $actual = Arr::camelize([
            'numeric',
            'foo' => 'bar',
            'foo-bar' => 'foobar',
            'baz_bat' => 'bazbat',
            'extra-values' => [
                'fooBar' => 'foobar',
                'BazBat' => 'bazbat',
            ],
        ]);

        $this->assertEquals([
            'numeric',
            'foo' => 'bar',
            'fooBar' => 'foobar',
            'bazBat' => 'bazbat',
            'extraValues' => [
                'fooBar' => 'foobar',
                'bazBat' => 'bazbat',
            ],
        ], $actual);
    }

    public function testDasherize()
    {
        $actual = Arr::dasherize([
            'numeric',
            'foo' => 'bar',
            'fooBar' => 'foobar',
            'baz_bat' => 'bazbat',
            'extraValues' => [
                'fooBar' => 'foobar',
                'BazBat' => 'bazbat',
                'foo-baz' => 'foobaz',
            ],
        ]);

        $this->assertEquals([
            'numeric',
            'foo' => 'bar',
            'foo-bar' => 'foobar',
            'baz-bat' => 'bazbat',
            'extra-values' => [
                'foo-bar' => 'foobar',
                'baz-bat' => 'bazbat',
                'foo-baz' => 'foobaz',
            ],
        ], $actual);
    }

    public function testDecamelize()
    {
        $actual = Arr::decamelize([
            'numeric',
            'foo' => 'bar',
            'fooBar' => 'foobar',
            'BazBat' => 'bazbat',
            'extraValues' => [
                'foo_bar' => 'foobar',
                'BazBat' => 'bazbat',
            ],
        ]);

        $this->assertEquals([
            'numeric',
            'foo' => 'bar',
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
            'extra_values' => [
                'foo_bar' => 'foobar',
                'baz_bat' => 'bazbat',
            ],
        ], $actual);
    }

    public function testUnderscore()
    {
        $actual = Arr::underscore([
            'numeric',
            'foo' => 'bar',
            'fooBar' => 'foobar',
            'BazBat' => 'bazbat',
            'extraValues' => [
                'foo_bar' => 'foobar',
                'BazBat' => 'bazbat',
                'foo-baz' => 'foobaz',
            ],
        ]);

        $this->assertEquals([
            'numeric',
            'foo' => 'bar',
            'foo_bar' => 'foobar',
            'baz_bat' => 'bazbat',
            'extra_values' => [
                'foo_bar' => 'foobar',
                'baz_bat' => 'bazbat',
                'foo_baz' => 'foobaz',
            ],
        ], $actual);
    }

    /**
     * @return array
     */
    public function methodsProvider()
    {
        return [
            ['camelize'],
            ['decamelize'],
            ['dasherize'],
            ['underscore'],
        ];
    }

    /**
     * Test that the conversion methods accept null as a value.
     *
     * @param $method
     * @dataProvider methodsProvider
     */
    public function testNull($method)
    {
        $actual = call_user_func(Arr::class . "::{$method}", null);

        $this->assertSame([], $actual);
    }
}
