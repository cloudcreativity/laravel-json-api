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

use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderInterface;
use CloudCreativity\LaravelJsonApi\Http\Headers\HeaderParameters;
use PHPUnit\Framework\TestCase;

class HeaderParametersTest extends TestCase
{
    public function test(): void
    {
        $accept = $this->createMock(AcceptHeaderInterface::class);
        $contentType = $this->createMock(HeaderInterface::class);

        $headers = new HeaderParameters($accept, $contentType);

        $this->assertSame($accept, $headers->getAcceptHeader());
        $this->assertSame($contentType, $headers->getContentTypeHeader());
    }

    public function testNoContentType(): void
    {
        $accept = $this->createMock(AcceptHeaderInterface::class);

        $headers = new HeaderParameters($accept, null);

        $this->assertSame($accept, $headers->getAcceptHeader());
        $this->assertNull($headers->getContentTypeHeader());
    }
}