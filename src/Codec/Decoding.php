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

namespace CloudCreativity\LaravelJsonApi\Codec;

use CloudCreativity\LaravelJsonApi\Contracts\Decoder\DecoderInterface;
use CloudCreativity\LaravelJsonApi\Decoder\JsonApiDecoder;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Http\Headers\MediaTypeParser;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;

/**
 * Class Decoding
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Decoding
{
    /**
     * @var MediaTypeInterface
     */
    private MediaTypeInterface $mediaType;

    /**
     * @var DecoderInterface
     */
    private DecoderInterface $decoder;

    /**
     * Create a decoding.
     *
     * @param string|MediaTypeInterface $mediaType
     * @param string|DecoderInterface $decoder
     * @return Decoding
     */
    public static function create($mediaType, $decoder): self
    {
        if (is_string($mediaType)) {
            $mediaType = MediaTypeParser::make()->parse($mediaType);
        }

        if (!$mediaType instanceof MediaTypeInterface) {
            throw new \InvalidArgumentException('Expecting a media type object or string.');
        }

        if (is_string($decoder)) {
            $decoder = app($decoder);
        }

        if (!$decoder instanceof DecoderInterface) {
            throw new \InvalidArgumentException('Expecting a decoder or decoder service name.');
        }

        return new self($mediaType, $decoder);
    }

    /**
     * @param $key
     * @param $value
     * @return Decoding
     */
    public static function fromArray($key, $value): self
    {
        if (is_numeric($key)) {
            $key = $value;
            $value = new JsonApiDecoder();
        }

        return self::create($key, $value);
    }

    /**
     * Decoding constructor.
     *
     * @param MediaTypeInterface $mediaType
     * @param DecoderInterface $decoder
     */
    public function __construct(MediaTypeInterface $mediaType, DecoderInterface $decoder)
    {
        $this->mediaType = $mediaType;
        $this->decoder = $decoder;
    }

    /**
     * @return MediaTypeInterface
     */
    public function getMediaType(): MediaTypeInterface
    {
        return $this->mediaType;
    }

    /**
     * @return DecoderInterface
     */
    public function getDecoder(): DecoderInterface
    {
        return $this->decoder;
    }

    /**
     * @return JsonApiDecoder
     */
    public function getJsonApiDecoder(): JsonApiDecoder
    {
        if ($this->decoder instanceof JsonApiDecoder) {
            return $this->decoder;
        }

        throw new RuntimeException('Decoder is not a JSON API decoder.');
    }

    /**
     * Will the decoding decode JSON API content?
     *
     * @return bool
     */
    public function isJsonApi(): bool
    {
        return $this->decoder instanceof JsonApiDecoder;
    }

    /**
     * @return bool
     */
    public function isNotJsonApi(): bool
    {
        return $this->isJsonApi();
    }

    /**
     * @param MediaTypeInterface $mediaType
     * @return bool
     */
    public function equalsTo(MediaTypeInterface $mediaType): bool
    {
        return $mediaType->matchesTo($this->mediaType);
    }
}
