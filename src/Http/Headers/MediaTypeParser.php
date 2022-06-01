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

use CloudCreativity\LaravelJsonApi\Factories\Factory;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\HeaderParametersParser as NeomerxParser;

class MediaTypeParser
{
    /**
     * @var NeomerxParser
     */
    private NeomerxParser $parser;

    /**
     * @return MediaTypeParser
     */
    public static function make(): self
    {
        return Factory::getInstance()->createMediaTypeParser();
    }

    /**
     * MediaTypeParser constructor.
     *
     * @param NeomerxParser $parser
     */
    public function __construct(NeomerxParser $parser)
    {
        $this->parser = $parser;
    }

    /**
     * Parse a string media type to a media type object.
     *
     * @param string $mediaType
     * @return MediaTypeInterface
     */
    public function parse(string $mediaType): MediaTypeInterface
    {
        return $this->parser->parseContentTypeHeader($mediaType);
    }
}
