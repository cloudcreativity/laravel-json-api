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

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Schema\SchemaContainer;
use CloudCreativity\LaravelJsonApi\Schema\SchemaFields;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
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
     * Set the encoding parameters.
     *
     * @param QueryParametersInterface|null $parameters
     * @return $this
     */
    public function withEncodingParameters(?QueryParametersInterface $parameters): self
    {
        if ($parameters) {
            $this
                ->withIncludedPaths($parameters->getIncludePaths() ?? [])
                ->withFieldSets($parameters->getFieldSets() ?? []);
        }

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
