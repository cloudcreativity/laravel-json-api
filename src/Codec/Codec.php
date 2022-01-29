<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Codec;

use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;

/**
 * Class Codec
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Codec
{

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Encoding
     */
    private $encoding;

    /**
     * @var Decoding|null
     */
    private $decoding;

    /**
     * Codec constructor.
     *
     * @param FactoryInterface $factory
     * @param ContainerInterface $container
     * @param Encoding $encoding
     * @param Decoding|null $decoding
     */
    public function __construct(
        FactoryInterface $factory,
        ContainerInterface $container,
        Encoding $encoding,
        ?Decoding $decoding
    ) {
        $this->factory = $factory;
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
     * @return EncoderInterface
     */
    public function getEncoder(): EncoderInterface
    {
        if ($this->willNotEncode()) {
            throw new \RuntimeException('Codec does not support encoding JSON API content.');
        }

        return $this->factory->createEncoder(
            $this->container,
            $this->encoding->getOptions()
        );
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

        return collect($mediaTypes)->contains(function ($mediaType, $index) use ($encoding) {
            return $encoding->equalsTo(MediaType::parse($index, $mediaType));
        });
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

        return collect($mediaTypes)->contains(function ($mediaType, $index) use ($decoding) {
            return $decoding->equalsTo(MediaType::parse($index, $mediaType));
        });
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
