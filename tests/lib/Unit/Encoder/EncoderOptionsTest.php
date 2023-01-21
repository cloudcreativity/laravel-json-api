<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Encoder;

use CloudCreativity\LaravelJsonApi\Encoder\EncoderOptions;
use PHPUnit\Framework\TestCase;

class EncoderOptionsTest extends TestCase
{
    public function test(): void
    {
        $options = new EncoderOptions(
            $opt = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
            $urlPrefix = '/api/v1',
            $depth = 345,
        );

        $this->assertSame($opt, $options->getOptions());
        $this->assertSame($urlPrefix, $options->getUrlPrefix());
        $this->assertSame($depth, $options->getDepth());
    }

    public function testDefaults(): void
    {
        $options = new EncoderOptions();

        $this->assertSame(0, $options->getOptions());
        $this->assertNull($options->getUrlPrefix());
        $this->assertSame(512, $options->getDepth());
    }
}