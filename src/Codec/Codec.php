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

use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Encoder\Encoder;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Headers\MediaTypeParser;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;

/**
 * Class Codec
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Codec
{
    /**
     * @var Factory
     */
    private Factory $factory;

    /**
     * @var MediaTypeParser
     */
    private MediaTypeParser $mediaTypeParser;

    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var Encoding
     */
    private Encoding $encoding;

    /**
     * @var Decoding|null
     */
    private ?Decoding $decoding;

    /**
     * Codec constructor.
     *
     * @param Factory $factory
     * @param MediaTypeParser $mediaTypeParser
     * @param ContainerInterface $container
     * @param Encoding $encoding
     * @param Decoding|null $decoding
     */
    public function __construct(
        Factory $factory,
        MediaTypeParser $mediaTypeParser,
        ContainerInterface $container,
        Encoding $encoding,
        ?Decoding $decoding
    ) {
        $this->factory = $factory;
        $this->mediaTypeParser = $mediaTypeParser;
        $this->container = $container;
        $this->encoding = $encoding;
        $this->decoding = $decoding;
    }

    /**
     * Will the codec encode JSON API content?
     *
     * @return bool
     */
    public function willEncode(): bool
    {
        return $this->encoding->hasOptions();
    }

    /**
     * Will the codec not encode JSON API content?
     *
     * @return bool
     */
    public function willNotEncode(): bool
    {
        return !$this->willEncode();
    }

    /**
     * @return Encoder
     */
    public function getEncoder(): Encoder
    {
        if ($this->willNotEncode()) {
            throw new \RuntimeException('Codec does not support encoding JSON API content.');
        }

        $encoder = $this->factory->createLaravelEncoder(
            $this->container,
        );

        $options = $this->encoding->getOptions();

        if ($options) {
            $encoder
                ->withEncodeOptions($options->getOptions())
                ->withEncodeDepth($options->getDepth())
                ->withUrlPrefix($options->getUrlPrefix() ?? '');
        }

        return $encoder;
    }

    /**
     * @return MediaTypeInterface
     */
    public function getEncodingMediaType(): MediaTypeInterface
    {
        return $this->encoding->getMediaType();
    }

    /**
     * Does the codec encode any of the supplied media types?
     *
     * @param string ...$mediaTypes
     * @return bool
     */
    public function encodes(string ...$mediaTypes): bool
    {
        $encoding = $this->getEncodingMediaType();

        return Collection::make($mediaTypes)->contains(
            fn($mediaType) => $encoding->equalsTo($this->mediaTypeParser->parse($mediaType))
        );
    }

    /**
     * Will the codec decode JSON API content?
     *
     * @return bool
     */
    public function canDecodeJsonApi(): bool
    {
        if (!$this->decoding) {
            return false;
        }

        return $this->decoding->isJsonApi();
    }

    /**
     * Will the codec not decode JSON API content?
     *
     * @return bool
     */
    public function cannotDecodeJsonApi(): bool
    {
        return !$this->canDecodeJsonApi();
    }

    /**
     * @return MediaTypeInterface|null
     */
    public function getDecodingMediaType(): ?MediaTypeInterface
    {
        return $this->decoding ? $this->decoding->getMediaType() : null;
    }

    /**
     * Does the codec decode any of the supplied media types?
     *
     * @param string ...$mediaTypes
     * @return bool
     */
    public function decodes(string ...$mediaTypes): bool
    {
        if (!$decoding = $this->getDecodingMediaType()) {
            return false;
        }

        return Collection::make($mediaTypes)->contains(
            fn($mediaType) => $decoding->equalsTo($this->mediaTypeParser->parse($mediaType))
        );
    }

    /**
     * Decode a JSON API document from the request content.
     *
     * @param $request
     * @return \stdClass|null
     */
    public function document($request): ?\stdClass
    {
        if ($this->cannotDecodeJsonApi()) {
            return null;
        }

        return $this->decoding->getJsonApiDecoder()->document($request);
    }

    /**
     * Retrieve array input from the request.
     *
     * @param $request
     * @return array
     */
    public function all($request): array
    {
        return $this->decoding ? $this->decoding->getDecoder()->decode($request) : [];
    }
}
