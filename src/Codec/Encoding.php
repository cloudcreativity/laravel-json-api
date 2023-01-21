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

use CloudCreativity\LaravelJsonApi\Encoder\EncoderOptions;
use CloudCreativity\LaravelJsonApi\Http\Headers\MediaTypeParser;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptMediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;

/**
 * Class Encoding
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Encoding
{
    /**
     * @var MediaTypeInterface
     */
    private MediaTypeInterface $mediaType;

    /**
     * @var EncoderOptions|null
     */
    private ?EncoderOptions $options;

    /**
     * Create an encoding that will encode JSON API content.
     *
     * @param string|MediaTypeInterface $mediaType
     * @param int $options
     * @param string|null $urlPrefix
     * @param int $depth
     * @return Encoding
     */
    public static function create(
        $mediaType,
        int $options = 0,
        string $urlPrefix = null,
        int $depth = 512
    ): self
    {
        if (!$mediaType instanceof MediaTypeInterface) {
            $mediaType = MediaTypeParser::make()->parse($mediaType);
        }

        return new self($mediaType, new EncoderOptions($options, $urlPrefix, $depth));
    }

    /**
     * Create an encoding for the JSON API media type.
     *
     * @param int $options
     * @param string|null $urlPrefix
     * @param int $depth
     * @return Encoding
     */
    public static function jsonApi(int $options = 0, string $urlPrefix = null, int $depth = 512): self
    {
        return self::create(
            MediaTypeInterface::JSON_API_MEDIA_TYPE,
            $options,
            $urlPrefix,
            $depth
        );
    }

    /**
     * Create an encoding that will not encode JSON API content.
     *
     * @param string|MediaTypeInterface $mediaType
     * @return Encoding
     */
    public static function custom($mediaType): self
    {
        if (!$mediaType instanceof MediaTypeInterface) {
            $mediaType = MediaTypeParser::make()->parse($mediaType);
        }

        return new self($mediaType, null);
    }

    /**
     * @param $key
     * @param $value
     * @param string|null $urlPrefix
     * @return Encoding
     */
    public static function fromArray($key, $value, string $urlPrefix = null): self
    {
        if (is_numeric($key)) {
            $key = $value;
            $value = 0;
        }

        return (false === $value) ? self::custom($key) : self::create($key, $value, $urlPrefix);
    }

    /**
     * Encoding constructor.
     *
     * @param MediaTypeInterface $mediaType
     * @param EncoderOptions|null $options
     *      the encoding options, if the encoding to JSON API content is supported.
     */
    public function __construct(MediaTypeInterface $mediaType, ?EncoderOptions $options)
    {
        $this->mediaType = $mediaType;
        $this->options = $options;
    }

    /**
     * @return MediaTypeInterface
     */
    public function getMediaType(): MediaTypeInterface
    {
        return $this->mediaType;
    }

    /**
     * Get the options, if the media type returns JSON API encoded content.
     *
     * @return EncoderOptions|null
     */
    public function getOptions(): ?EncoderOptions
    {
        return $this->options;
    }

    /**
     * Will the encoding encode JSON API content?
     *
     * @return bool
     */
    public function hasOptions(): bool
    {
        return !is_null($this->options);
    }

    /**
     * Is the encoding for any of the supplied media types?
     *
     * @param string ...$mediaTypes
     * @return bool
     */
    public function is(string ...$mediaTypes): bool
    {
        $mediaTypes = Collection::make($mediaTypes)->map(
            fn($mediaType) => MediaTypeParser::make()->parse($mediaType)
        );

        return $this->any(...$mediaTypes);
    }

    /**
     * @param MediaTypeInterface ...$mediaTypes
     * @return bool
     */
    public function any(MediaTypeInterface ...$mediaTypes): bool
    {
        foreach ($mediaTypes as $mediaType) {
            if ($this->matchesTo($mediaType)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Does the encoding match the supplied media type?
     *
     * @param MediaTypeInterface $mediaType
     * @return bool
     */
    public function matchesTo(MediaTypeInterface $mediaType): bool
    {
        return $this->getMediaType()->matchesTo($mediaType);
    }

    /**
     * Is the encoding acceptable?
     *
     * @param AcceptMediaTypeInterface $mediaType
     * @return bool
     */
    public function accept(AcceptMediaTypeInterface $mediaType): bool
    {
        // if quality factor 'q' === 0 it means this type is not acceptable (RFC 2616 #3.9)
        if (0 === $mediaType->getQuality()) {
            return false;
        }

        return $this->matchesTo($mediaType);
    }
}
