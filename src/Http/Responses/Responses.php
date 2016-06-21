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

use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
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
     * @var JsonApiService
     */
    private $service;

    /**
     * Responses constructor.
     * @param JsonApiService $service
     */
    public function __construct(JsonApiService $service)
    {
        $this->service = $service;
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
        return $this->service->api()->getEncoder();
    }

    /**
     * @return null|string
     */
    protected function getUrlPrefix()
    {
        return $this->service->api()->getUrlPrefix();
    }


    /**
     * @return EncodingParametersInterface|null
     */
    protected function getEncodingParameters()
    {
        if (!$this->service->hasRequest()) {
            return null;
        }

        return $this->service->request()->getEncodingParameters();
    }

    /**
     * @return ContainerInterface
     */
    protected function getSchemaContainer()
    {
        return $this->service->api()->getSchemas();
    }

    /**
     * @return SupportedExtensionsInterface|null
     */
    protected function getSupportedExtensions()
    {
        return $this->service->api()->getSupportedExts();
    }

    /**
     * @return MediaTypeInterface
     */
    protected function getMediaType()
    {
        $type = $this
            ->service
            ->api()
            ->getCodecMatcher()
            ->getEncoderRegisteredMatchedType();

        if (!$type instanceof MediaTypeInterface) {
            throw new RuntimeException('No matching media type for encoded JSON-API response.');
        }

        return $type;
    }

}
