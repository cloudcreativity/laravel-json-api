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

namespace CloudCreativity\LaravelJsonApi\Encoder;

use CloudCreativity\LaravelJsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Schema\SchemaContainer;
use CloudCreativity\LaravelJsonApi\Schema\SchemaFields;
use Generator;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;
use Neomerx\JsonApi\Encoder\Encoder as BaseEncoder;
use RuntimeException;

/**
 * Class Encoder
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Encoder extends BaseEncoder implements SerializerInterface
{
    /**
     * @var DataAnalyser
     */
    private DataAnalyser $dataAnalyser;

    /**
     * @var bool
     */
    private bool $hasIncludePaths = false;

    /**
     * Assert that the encoder is an extended encoder.
     *
     * @param EncoderInterface $encoder
     * @return Encoder
     */
    public static function assertInstance(EncoderInterface $encoder): self
    {
        if ($encoder instanceof self) {
            return $encoder;
        }

        throw new RuntimeException('Expecting an extended encoder instance.');
    }

    /**
     * Encoder constructor.
     *
     * @param FactoryInterface $factory
     * @param SchemaContainerInterface $container
     * @param DataAnalyser $dataAnalyser
     */
    public function __construct(
        FactoryInterface $factory,
        SchemaContainerInterface $container,
        DataAnalyser $dataAnalyser
    ) {
        parent::__construct($factory, $container);
        $this->dataAnalyser = $dataAnalyser;
    }

    /**
     * Set the encoding parameters.
     *
     * @param QueryParametersInterface|null $parameters
     * @return $this
     */
    public function withEncodingParameters(?QueryParametersInterface $parameters): self
    {
        if ($parameters) {
            $this
                ->withIncludedPaths($parameters->getIncludePaths())
                ->withFieldSets($parameters->getFieldSets() ?? []);
        }

        return $this;
    }

    /**
     * @param iterable|null $paths
     * @return $this
     */
    public function withIncludedPaths(?iterable $paths): EncoderInterface
    {
        parent::withIncludedPaths($paths ?? []);
        $this->hasIncludePaths = (null !== $paths);

        return $this;
    }

    /**
     * Set the encoder options.
     *
     * @param EncoderOptions|null $options
     * @return $this
     */
    public function withEncoderOptions(?EncoderOptions $options): self
    {
        if ($options) {
            $this
                ->withEncodeOptions($options->getOptions())
                ->withEncodeDepth($options->getDepth())
                ->withUrlPrefix($options->getUrlPrefix());
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function serializeData($data): array
    {
        return $this->encodeDataToArray($data);
    }

    /**
     * @inheritDoc
     */
    public function serializeIdentifiers($data): array
    {
        return $this->encodeIdentifiersToArray($data);
    }

    /**
     * @inheritDoc
     */
    public function serializeError(ErrorInterface $error): array
    {
        return $this->encodeErrorToArray($error);
    }

    /**
     * @inheritDoc
     */
    public function serializeErrors($errors): array
    {
        return $this->encodeErrorsToArray($errors);
    }

    /**
     * @inheritDoc
     */
    public function serializeMeta($meta): array
    {
        return $this->encodeMetaToArray($meta);
    }

    /**
     * @param iterable|object|null $data
     * @return array
     */
    protected function encodeDataToArray($data): array
    {
        if (false === $this->hasIncludePaths) {
            if ($data instanceof Generator) {
                $data = iterator_to_array($data);
            }
            parent::withIncludedPaths($this->dataAnalyser->getIncludePaths($data));
            $this->hasIncludePaths = true;
        }

        return parent::encodeDataToArray($data);
    }

    /**
     * @return Factory
     */
    protected static function createFactory(): Factory
    {
        return app(Factory::class);
    }

    /**
     * @inheritDoc
     */
    protected function getSchemaContainer(): SchemaContainerInterface
    {
        $schemaContainer = parent::getSchemaContainer();

        if ($schemaContainer instanceof SchemaContainer) {
            $schemaContainer->setSchemaFields(
                new SchemaFields($this->getIncludePaths(), $this->getFieldSets())
            );
        }

        return $schemaContainer;
    }
}
