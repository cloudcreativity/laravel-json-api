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

namespace CloudCreativity\LaravelJsonApi\Http;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Codec\Decoding;
use CloudCreativity\LaravelJsonApi\Codec\DecodingList;
use CloudCreativity\LaravelJsonApi\Codec\Encoding;
use CloudCreativity\LaravelJsonApi\Codec\EncodingList;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ContentNegotiator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ContentNegotiator implements ContentNegotiatorInterface
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Api
     */
    protected $api;

    /**
     * Supported encoding media types.
     *
     * Configure supported encoding media types for this negotiator here.
     * These are merged with the encoding media types from your API. The format
     * of this array is identical to the format in your API config.
     *
     * @var array
     */
    protected $encoding = [];

    /**
     * Supported decoding media types.
     *
     * Configure supported decoding media types for this negotiator here.
     * These are merged with the decoding media types from your API. The format
     * of this array is identical to the format in your API config.
     *
     * @var array
     */
    protected $decoding = [];

    /**
     * @var Factory
     */
    private $factory;

    /**
     * ContentNegotiator constructor.
     *
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param Request $request
     * @return ContentNegotiatorInterface
     */
    public function withRequest(Request $request): ContentNegotiatorInterface
    {
        $this->request = $request;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function withApi(Api $api): ContentNegotiatorInterface
    {
        $this->api = $api;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function encoding(AcceptHeaderInterface $header, $record = null): Encoding
    {
        $fn = method_exists($this, 'encodingsForOne') ? 'encodingsForOne' : 'encodingMediaTypes';
        $supported = $this->{$fn}($record);

        return $this->checkAcceptTypes($header, $supported);
    }

    /**
     * @inheritDoc
     */
    public function encodingForMany(AcceptHeaderInterface $header): Encoding
    {
        $fn = method_exists($this, 'encodingsForMany') ? 'encodingsForMany' : 'encodingMediaTypes';
        $supported = $this->{$fn}();

        return $this->checkAcceptTypes($header, $supported);
    }

    /**
     * @inheritdoc
     */
    public function decoding(HeaderInterface $header, $record): Decoding
    {
        $fn = method_exists($this, 'decodingsForResource') ? 'decodingsForResource' : 'decodingMediaTypes';
        $supported = $this->{$fn}($record);

        return $this->checkContentType($header, $supported);
    }

    /**
     * @inheritDoc
     */
    public function decodingForRelationship(HeaderInterface $header, $record, string $field): Decoding
    {
        $fn = method_exists($this, 'decodingsForRelationship') ? 'decodingsForRelationship' : 'decodingMediaTypes';
        $supported = $this->{$fn}($record, $field);

        return $this->checkContentType($header, $supported);
    }

    /**
     * @return EncodingList
     */
    protected function encodingMediaTypes(): EncodingList
    {
        return $this->api->getEncodings()->merge(
            EncodingList::fromArray($this->encoding, $this->api->getUrl()->toString())
        );
    }

    /**
     * @return DecodingList
     */
    protected function decodingMediaTypes(): DecodingList
    {
        return $this->api->getDecodings()->merge(
            DecodingList::fromArray($this->decoding)
        );
    }

    /**
     * @param AcceptHeaderInterface $header
     * @param EncodingList $supported
     * @return Encoding
     * @throws HttpException
     */
    protected function checkAcceptTypes(AcceptHeaderInterface $header, EncodingList $supported): Encoding
    {
        if (!$codec = $supported->acceptable($header)) {
            throw $this->notAcceptable($header);
        }

        return $codec;
    }

    /**
     * @param HeaderInterface $header
     * @param DecodingList $supported
     * @return Decoding
     * @throws HttpException
     */
    protected function checkContentType(HeaderInterface $header, DecodingList $supported): Decoding
    {
        if (!$decoder = $supported->forHeader($header)) {
            throw $this->unsupportedMediaType();
        }

        return $decoder;
    }

    /**
     * Get the exception if the Accept header is not acceptable.
     *
     * @param AcceptHeaderInterface $header
     * @return HttpException
     * @todo add translation
     */
    protected function notAcceptable(AcceptHeaderInterface $header): HttpException
    {
        return new HttpException(
            self::HTTP_NOT_ACCEPTABLE,
            "The requested resource is capable of generating only content not acceptable "
            . "according to the Accept headers sent in the request."
        );
    }

    /**
     * @param HeaderInterface $header
     * @param string $mediaType
     * @return bool
     */
    protected function isMediaType(HeaderInterface $header, string $mediaType): bool
    {
        $mediaType = MediaType::parse(0, $mediaType);

        return collect($header->getMediaTypes())->contains(function (MediaTypeInterface $check) use ($mediaType) {
            return $check->equalsTo($mediaType);
        });
    }

    /**
     * Is the header the JSON API media-type?
     *
     * @param HeaderInterface $header
     * @return bool
     */
    protected function isJsonApi(HeaderInterface $header): bool
    {
        return $this->isMediaType($header, MediaTypeInterface::JSON_API_MEDIA_TYPE);
    }

    /**
     * @param HeaderInterface $header
     * @return bool
     */
    protected function isNotJsonApi(HeaderInterface $header): bool
    {
        return !$this->isJsonApi($header);
    }

    /**
     * Get the exception if the Content-Type header media type is not supported.
     *
     * @return HttpException
     * @todo add translation
     */
    protected function unsupportedMediaType(): HttpException
    {
        return new HttpException(
            self::HTTP_UNSUPPORTED_MEDIA_TYPE,
            'The request entity has a media type which the server or resource does not support.'
        );
    }

}
