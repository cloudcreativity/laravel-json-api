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

use CloudCreativity\LaravelJsonApi\Rules\AllowedSortParameters;
use PHPUnit\Framework\TestCase;

class AllowedSortParametersTest extends TestCase
{

    public function test()
    {
        $rule = new AllowedSortParameters(['foo', 'bar']);

        $this->assertTrue($rule->passes('sort', 'foo,-bar'));
        $this->assertTrue($rule->passes('sort', '+foo,bar'));
        $this->assertFalse($rule->passes('sort', 'foo,baz'));
        $this->assertFalse($rule->passes('sort', 'foobar'));
    }

    public function testWithMethods()
    {
        $rule = (new AllowedSortParameters())
            ->allow('foo', 'bar', 'foobar')
            ->forget('foobar');

        $this->assertTrue($rule->passes('sort', 'foo,-bar'));
        $this->assertFalse($rule->passes('sort', 'foo,baz'));
        $this->assertFalse($rule->passes('sort', 'foobar'));
    }

    public function testAny()
    {
        $rule = new AllowedSortParameters(null);

        $this->assertTrue($rule->passes('sort', 'foo,-bar,baz,-bat'));
    }

}
