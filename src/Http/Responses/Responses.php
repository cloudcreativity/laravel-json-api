<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Http\Responses;

use CloudCreativity\LaravelJsonApi\Services\JsonApiContainer;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Encoder\EncoderInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\SupportedExtensionsInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;
use Neomerx\JsonApi\Http\Responses as AbstractResponses;
use RuntimeException;

/**
 * Class Responses
 * @package CloudCreativity\LaravelJsonApi
 */
class Responses extends AbstractResponses
{

    /**
     * @var JsonApiContainer
     */
    private $container;

    /**
     * Responses constructor.
     * @param JsonApiContainer $container
     */
    public function __construct(JsonApiContainer $container)
    {
        $this->container = $container;
    }

    /**
     * @param null|string $content
     * @param int $statusCode
     * @param array $headers
     * @return Response
     */
    protected function createResponse($content, $statusCode, array $headers)
    {
        return new Response($content, $statusCode, $headers);
    }

    /**
     * @return EncoderInterface|null
     */
    protected function getEncoder()
    {
        return $this->container->getEncoder();
    }

    /**
     * @return null|string
     */
    protected function getUrlPrefix()
    {
        return $this->container->getUrlPrefix();
    }


    /**
     * @return EncodingParametersInterface|null
     */
    protected function getEncodingParameters()
    {
        return $this->container->getEncodingParameters();
    }

    /**
     * @return ContainerInterface
     */
    protected function getSchemaContainer()
    {
        return $this->container->getSchemaContainer();
    }

    /**
     * @return SupportedExtensionsInterface|null
     */
    protected function getSupportedExtensions()
    {
        return $this->container->getSupportExtensions();
    }

    /**
     * @return MediaTypeInterface
     */
    protected function getMediaType()
    {
        $type = $this
            ->container
            ->getCodecMatcher()
            ->getEncoderRegisteredMatchedType();

        if (!$type instanceof MediaTypeInterface) {
            throw new RuntimeException('No matching media type for encoded Json-Api response.');
        }

        return $type;
    }

}
