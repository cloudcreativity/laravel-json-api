<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Repositories;

use CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface;
use Generator;
use Neomerx\JsonApi\Codec\CodecMatcher;
use Neomerx\JsonApi\Contracts\Decoder\DecoderInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Decoders\ObjectDecoder;
use Neomerx\JsonApi\Encoder\EncoderOptions;
use Neomerx\JsonApi\Factories\Factory;
use Neomerx\JsonApi\Http\Headers\MediaType;
use RuntimeException;

/**
 * Class CodecMatcherRepository
 *
 * @package CloudCreativity\JsonApi
 *
 * Example config:
 *
 * ````
 * [
 *      'encoders' => [
 *          // Media type without any settings.
 *          'application/vnd.api+json'
 *          // Media type with encoder options.
 *          'application/json' => JSON_BIGINT_AS_STRING,
 *          // Media type with options and depth.
 *          'text/plain' => [
 *              'options' => JSON_PRETTY_PRINT,
 *              'depth' => 125,
 *          ],
 *      ],
 *      'decoders' => [
 *          // Defaults to using DocumentDecoder
 *          'application/vnd.api+json',
 *          // Specified decoder class
 *          'application/json' => ArrayDecoder::class,
 *      ],
 * ]
 * ```
 *
 */
class CodecMatcherRepository implements CodecMatcherRepositoryInterface
{

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var string|null
     */
    private $urlPrefix;

    /**
     * @var ContainerInterface
     */
    private $schemas;

    /**
     * @var array
     */
    private $encoders = [];

    /**
     * @var array
     */
    private $decoders = [];

    /**
     * @param FactoryInterface|null $factory
     */
    public function __construct(FactoryInterface $factory = null)
    {
        $this->factory = $factory ?: new Factory();
    }

    /**
     * @param $urlPrefix
     * @return $this
     */
    public function registerUrlPrefix($urlPrefix)
    {
        $this->urlPrefix = ($urlPrefix) ?: null;

        return $this;
    }

    /**
     * @return null|string
     */
    public function getUrlPrefix()
    {
        return $this->urlPrefix;
    }

    /**
     * @param ContainerInterface $schemas
     * @return $this
     */
    public function registerSchemas(ContainerInterface $schemas)
    {
        $this->schemas = $schemas;

        return $this;
    }

    /**
     * @return ContainerInterface
     */
    public function getSchemas()
    {
        if (!$this->schemas instanceof ContainerInterface) {
            throw new RuntimeException('No schemas set.');
        }

        return $this->schemas;
    }

    /**
     * @return CodecMatcher
     */
    public function getCodecMatcher()
    {
        $codecMatcher = new CodecMatcher();

        foreach ($this->getEncoders() as $mediaType => $encoder) {
            $codecMatcher->registerEncoder($this->normalizeMediaType($mediaType), $encoder);
        }

        foreach ($this->getDecoders() as $mediaType => $decoder) {
            $codecMatcher->registerDecoder($this->normalizeMediaType($mediaType), $decoder);
        }

        return $codecMatcher;
    }

    /**
     * @param array $config
     * @return $this
     */
    public function configure(array $config)
    {
        $encoders = isset($config[static::ENCODERS]) ? (array) $config[static::ENCODERS] : [];
        $decoders = isset($config[static::DECODERS]) ? (array) $config[static::DECODERS] : [];

        $this->configureEncoders($encoders)
            ->configureDecoders($decoders);

        return $this;
    }

    /**
     * @param array $encoders
     * @return $this
     */
    private function configureEncoders(array $encoders)
    {
        $this->encoders = [];

        foreach ($encoders as $mediaType => $options) {

            if (is_numeric($mediaType)) {
                $mediaType = $options;
                $options = [];
            }

            $this->encoders[$mediaType] = $this->normalizeEncoder($options);
        }

        return $this;
    }

    /**
     * @param $options
     * @return array
     */
    private function normalizeEncoder($options)
    {
        $defaults = [
            static::OPTIONS => 0,
            static::DEPTH => 512,
        ];

        if (!is_array($options)) {
            $options = [
                static::OPTIONS => $options,
            ];
        }

        return array_merge($defaults, $options);
    }

    /**
     * @return Generator
     */
    private function getEncoders()
    {
        /** @var array $encoder */
        foreach ($this->encoders as $mediaType => $encoder) {

            $closure = function () use ($encoder) {
                $options = $encoder[static::OPTIONS];
                $depth = $encoder[static::DEPTH];
                $encOptions = new EncoderOptions($options, $this->getUrlPrefix(), $depth);

                return $this->factory->createEncoder($this->getSchemas(), $encOptions);
            };

            yield $mediaType => $closure;
        }
    }

    /**
     * @param array $decoders
     * @return $this
     */
    private function configureDecoders(array $decoders)
    {
        $this->decoders = $decoders;

        return $this;
    }

    /**
     * @return Generator
     */
    private function getDecoders()
    {
        foreach ($this->decoders as $mediaType => $decoderClass) {

            if (is_numeric($mediaType)) {
                $mediaType = $decoderClass;
                $decoderClass = ObjectDecoder::class;
            }

            $closure = function () use ($decoderClass) {

                if (!class_exists($decoderClass)) {
                    throw new RuntimeException(sprintf('Invalid decoder class: %s', $decoderClass));
                }

                $decoder = new $decoderClass();

                if (!$decoder instanceof DecoderInterface) {
                    throw new RuntimeException(sprintf('Class %s is not a decoder class.', $decoderClass));
                }

                return $decoder;
            };

            yield $mediaType => $closure;
        }
    }

    /**
     * @param string $mediaType
     * @return MediaTypeInterface
     */
    private function normalizeMediaType($mediaType)
    {
        return MediaType::parse(0, $mediaType);
    }
}
