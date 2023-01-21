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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Http\Headers;

use CloudCreativity\LaravelJsonApi\Http\Headers\Header;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use PHPUnit\Framework\TestCase;

class HeaderTest extends TestCase
{
    public function test(): void
    {
        $header = new Header('Content-Type', $mediaTypes = [
            $this->createMock(MediaTypeInterface::class),
            $this->createMock(MediaTypeInterface::class),
        ]);

        $this->assertSame('Content-Type', $header->getName());
        $this->assertSame($mediaTypes, $header->getMediaTypes());
    }

    public function testNotMediaTypes(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Header('Content-Type', [new \DateTime()]);
    }
}