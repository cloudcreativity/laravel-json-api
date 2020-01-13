<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Http\Headers;

use CloudCreativity\LaravelJsonApi\Contracts\Document\MutableErrorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeadersCheckerInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException as E;

class RestrictiveHeadersChecker implements HeadersCheckerInterface
{

    const NOT_ACCEPTABLE = 'headers:not-acceptable';
    const MULTIPLE_MEDIA_TYPES = 'headers:multiple-media-types';
    const UNSUPPORTED_MEDIA_TYPE = 'headers:unsupported-media-type';

    /**
     * @var CodecMatcherInterface
     */
    private $codecMatcher;

    /**
     * @var ErrorRepositoryInterface
     */
    private $errors;

    /**
     * RestrictiveHeadersChecker constructor.
     *
     * @param CodecMatcherInterface $codecMatcher
     * @param ErrorRepositoryInterface $errors
     */
    public function __construct(CodecMatcherInterface $codecMatcher, ErrorRepositoryInterface $errors)
    {
        $this->codecMatcher = $codecMatcher;
        $this->errors = $errors;
    }

    /**
     * @param HeaderParametersInterface $parameters
     *
     * @return void
     */
    public function checkHeaders(HeaderParametersInterface $parameters)
    {
        // Note: for these checks the order is specified by spec. See details inside.

        $this->checkAcceptHeader($parameters);

        // clients must send content type only if the request has body so there is no point in checking
        // `Content-Type` header if the original request is bodiless.
        if ($parameters->getContentTypeHeader() !== null) {
            $this->checkContentTypeHeader($parameters);
        }
    }

    /**
     * @param HeaderParametersInterface $parameters
     *
     * @return void
     */
    protected function checkAcceptHeader(HeaderParametersInterface $parameters)
    {
        $this->codecMatcher->matchEncoder($parameters->getAcceptHeader());

        // From spec: Servers MUST respond with a 406 Not Acceptable status code
        // if a request's Accept header contains the JSON API media type and all
        // instances of that media type are modified with media type parameters.

        // We return 406 if no match found for encoder (media type with or wo parameters)
        // If no encoders were configured for media types with parameters we return 406 anyway
        if ($this->codecMatcher->getEncoderHeaderMatchedType() === null) {
            throw new E([$this->createNotAcceptableError()], E::HTTP_CODE_NOT_ACCEPTABLE);
        }
    }

    /**
     * @param HeaderParametersInterface $parameters
     *
     * @return void
     */
    protected function checkContentTypeHeader(HeaderParametersInterface $parameters)
    {
        // Do not allow specify more than 1 media type for input data. Otherwise which one is correct?
        if (count($parameters->getContentTypeHeader()->getMediaTypes()) > 1) {
            throw new E([$this->createMultipleMediaTypesError()], E::HTTP_CODE_BAD_REQUEST);
        }

        $this->codecMatcher->matchDecoder($parameters->getContentTypeHeader());

        // From spec: Servers MUST respond with a 415 Unsupported Media Type status code
        // if a request specifies the header Content-Type: application/vnd.api+json with
        // any media type parameters.

        // We return 415 if no match found for decoder (media type with or wo parameters)
        // If no decoders were configured for media types with parameters we return 415 anyway
        if ($this->codecMatcher->getDecoderHeaderMatchedType() === null) {
            throw new E([$this->createUnsupportedMediaTypeError()], E::HTTP_CODE_UNSUPPORTED_MEDIA_TYPE);
        }
    }

    /**
     * @return MutableErrorInterface
     */
    protected function createNotAcceptableError()
    {
        return $this->errors->error(self::NOT_ACCEPTABLE);
    }

    /**
     * @return MutableErrorInterface
     */
    protected function createMultipleMediaTypesError()
    {
        return $this->errors->error(self::MULTIPLE_MEDIA_TYPES);
    }

    /**
     * @return MutableErrorInterface
     */
    protected function createUnsupportedMediaTypeError()
    {
        return $this->errors->error(self::UNSUPPORTED_MEDIA_TYPE);
    }
}
