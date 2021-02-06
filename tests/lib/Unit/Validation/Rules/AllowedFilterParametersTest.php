<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Rules\AllowedFilterParameters;
use PHPUnit\Framework\TestCase;

class AllowedFilterParametersTest extends TestCase
{

    public function test()
    {
        $rule = new AllowedFilterParameters(['foo', 'bar']);

        $this->assertTrue($rule->passes('filter', ['foo' => 'foobar', 'bar' => 'bazbat']));
        $this->assertFalse($rule->passes('filter', ['foo' => 'foobar', 'baz' => 'bazbat']));
    }

    public function testWithMethods()
    {
        $rule = (new AllowedFilterParameters())
            ->allow('foo', 'bar', 'id')
            ->forget('id');

        $this->assertTrue($rule->passes('filter', ['foo' => 'foobar', 'bar' => 'bazbat']));
        $this->assertFalse($rule->passes('filter', ['foo' => 'foobar', 'baz' => 'bazbat']));
        $this->assertFalse($rule->passes('filter', ['id' => '1']));
    }

    public function testAny()
    {
        $rule = new AllowedFilterParameters(null);

        $this->assertTrue($rule->passes('filter', [
            'foo' => 'bar',
        ]));
    }

}
