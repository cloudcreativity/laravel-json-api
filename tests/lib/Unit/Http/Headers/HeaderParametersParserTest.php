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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Http\Headers;

use ArrayIterator;
use CloudCreativity\LaravelJsonApi\Http\Headers\AcceptHeader;
use CloudCreativity\LaravelJsonApi\Http\Headers\Header;
use CloudCreativity\LaravelJsonApi\Http\Headers\HeaderParametersParser;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptMediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersParserInterface as NeomerxHeaderParametersParser;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

class HeaderParametersParserTest extends TestCase
{
    /**
     * @var NeomerxHeaderParametersParser|MockObject
     */
    private NeomerxHeaderParametersParser $neomerxParser;

    /**
     * @var HeaderParametersParser
     */
    private HeaderParametersParser $parser;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new HeaderParametersParser(
            $this->neomerxParser = $this->createMock(NeomerxHeaderParametersParser::class),
        );
    }

    public function test(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getHeader')->willReturnMap([
            ['Accept', ['fake-accept-header']],
            ['Content-Type', ['fake-content-type']],
        ]);

        $this->neomerxParser
            ->expects($this->once())
            ->method('parseContentTypeHeader')
            ->with('fake-content-type')
            ->willReturn($contentMediaType = $this->createMock(MediaTypeInterface::class));

        $acceptMediaTypes = [
            $this->createMock(AcceptMediaTypeInterface::class),
            $this->createMock(AcceptMediaTypeInterface::class),
        ];

        $this->neomerxParser
            ->expects($this->once())
            ->method('parseAcceptHeader')
            ->with('fake-accept-header')
            ->willReturn($acceptMediaTypes);

        $actual = $this->parser->parse($request);

        $this->assertEquals(new AcceptHeader($acceptMediaTypes), $actual->getAcceptHeader());
        $this->assertEquals(new Header('Content-Type', [$contentMediaType]), $actual->getContentTypeHeader());
    }

    public function testNoContentTypeAndTraversableAcceptMediaTypes(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request
            ->expects($this->once())
            ->method('getHeader')
            ->with('Accept')
            ->willReturn(['fake-accept-header']);

        $this->neomerxParser
            ->expects($this->never())
            ->method('parseContentTypeHeader');

        $acceptMediaType1 = $this->createMock(AcceptMediaTypeInterface::class);
        $acceptMediaType2 = $this->createMock(AcceptMediaTypeInterface::class);

        $this->neomerxParser
            ->expects($this->once())
            ->method('parseAcceptHeader')
            ->with('fake-accept-header')
            ->willReturn(new ArrayIterator([$acceptMediaType1, $acceptMediaType2]));

        $actual = $this->parser->parse($request, false);

        $this->assertEquals(new AcceptHeader([
            $acceptMediaType1,
            $acceptMediaType2,
        ]), $actual->getAcceptHeader());
        $this->assertNull($actual->getContentTypeHeader());
    }
}