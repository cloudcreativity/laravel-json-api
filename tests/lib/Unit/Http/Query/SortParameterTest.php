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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Http\Query;

use CloudCreativity\LaravelJsonApi\Http\Query\SortParameter;
use PHPUnit\Framework\TestCase;

class SortParameterTest extends TestCase
{
    public function testAscending(): void
    {
        $param = new SortParameter('createdAt', true);

        $this->assertSame('createdAt', $param->getField());
        $this->assertTrue($param->isAscending());
        $this->assertFalse($param->isDescending());
        $this->assertSame('createdAt', (string) $param);
    }

    public function testDescending(): void
    {
        $param = new SortParameter('updatedAt', false);

        $this->assertSame('updatedAt', $param->getField());
        $this->assertFalse($param->isAscending());
        $this->assertTrue($param->isDescending());
        $this->assertSame('-updatedAt', (string) $param);
    }
}