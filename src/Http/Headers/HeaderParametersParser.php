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

namespace CloudCreativity\LaravelJsonApi\Http\Headers;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderParametersParserInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersParserInterface as NeomerxHeaderParametersParser;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Psr\Http\Message\ServerRequestInterface;
use Traversable;

class HeaderParametersParser implements HeaderParametersParserInterface
{
    /**
     * @var NeomerxHeaderParametersParser
     */
    private NeomerxHeaderParametersParser $parser;

    /**
     * HeaderParametersParser constructor.
     *
     * @param NeomerxHeaderParametersParser $parser
     */
    public function __construct(NeomerxHeaderParametersParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * @inheritDoc
     */
    public function parse(ServerRequestInterface $request, bool $checkContentType = true): HeaderParametersInterface
    {
        $contentType = null;

        if ($checkContentType === true) {
            $contentMediaType = $this->parser->parseContentTypeHeader(
                $this->getHeader($request, HeaderInterface::HEADER_CONTENT_TYPE)
            );
            $contentType = new Header(
                HeaderInterface::HEADER_CONTENT_TYPE,
                [$contentMediaType]
            );
        }

        $acceptMediaTypes = $this->parser->parseAcceptHeader(
            $this->getHeader($request, HeaderInterface::HEADER_ACCEPT)
        );

        if ($acceptMediaTypes instanceof Traversable) {
            $acceptMediaTypes = iterator_to_array($acceptMediaTypes);
        }

        return new HeaderParameters(
            new AcceptHeader($acceptMediaTypes),
            $contentType,
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @param string $name
     * @return string
     */
    private function getHeader(ServerRequestInterface $request, string $name): string
    {
        $value = $request->getHeader($name);
        if (empty($value) === false) {
            $value = $value[0];
            if (empty($value) === false) {
                return $value;
            }
        }

        return MediaTypeInterface::JSON_API_MEDIA_TYPE;
    }
}
