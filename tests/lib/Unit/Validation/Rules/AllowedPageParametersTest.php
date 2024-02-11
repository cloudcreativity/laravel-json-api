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

use CloudCreativity\LaravelJsonApi\Rules\AllowedPageParameters;
use PHPUnit\Framework\TestCase;

class AllowedPageParametersTest extends TestCase
{

    public function test()
    {
        $rule = new AllowedPageParameters(['foo', 'bar']);

        $this->assertTrue($rule->passes('page', ['foo' => 'foobar', 'bar' => 'bazbat']));
        $this->assertFalse($rule->passes('page', ['foo' => 'foobar', 'baz' => 'bazbat']));
    }

    public function testWithMethods()
    {
        $rule = (new AllowedPageParameters())
            ->allow('foo', 'bar', 'foobar')
            ->forget('foobar');

        $this->assertTrue($rule->passes('page', ['foo' => 'foobar', 'bar' => 'bazbat']));
        $this->assertFalse($rule->passes('page', ['foo' => 'foobar', 'baz' => 'bazbat']));
        $this->assertFalse($rule->passes('page', ['foobar' => '1']));
    }

    public function testAny()
    {
        $rule = new AllowedPageParameters(null);

        $this->assertTrue($rule->passes('page', [
            'foo' => 'bar',
        ]));
    }

}
