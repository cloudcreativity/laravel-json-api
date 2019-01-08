<?php

namespace CloudCreativity\LaravelJsonApi\Http;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Codec\Decoding;
use CloudCreativity\LaravelJsonApi\Codec\DecodingList;
use CloudCreativity\LaravelJsonApi\Codec\Encoding;
use CloudCreativity\LaravelJsonApi\Codec\EncodingList;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface;
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
        $codecs = $record ? $this->encodingsForResource($record) : $this->encodingsForCreateResource();

        return $this->checkAcceptTypes($header, $codecs);
    }

    /**
     * @inheritDoc
     */
    public function encodingForMany(AcceptHeaderInterface $header): Encoding
    {
        $codecs = $this->encodingsForMany();

        return $this->checkAcceptTypes($header, $codecs);
    }

    /**
     * @inheritdoc
     */
    public function decoding(HeaderInterface $header, $record): Decoding
    {
        $supported = $record ? $this->decodingsForResource($record) : $this->decodingsForCreateResource();

        return $this->checkContentType($header, $supported);
    }

    /**
     * @inheritDoc
     */
    public function decodingForRelationship(HeaderInterface $header, $record, string $field): Decoding
    {
        return $this->checkContentType(
            $header,
            $this->decodingsForRelationship($record, $field)
        );
    }

    /**
     * Get encodings for a create resource response.
     *
     * @return EncodingList
     */
    protected function encodingsForCreateResource(): EncodingList
    {
        return $this->supportedEncodings();
    }

    /**
     * Get encodings for a resource response.
     *
     * @param mixed $record
     * @return EncodingList
     */
    protected function encodingsForResource($record): EncodingList
    {
        return $this->supportedEncodings();
    }

    /**
     * Get encodings for a zero-to-many resource response.
     *
     * @return EncodingList
     */
    protected function encodingsForMany(): EncodingList
    {
        return $this->supportedEncodings();
    }

    /**
     * @return EncodingList
     */
    protected function supportedEncodings(): EncodingList
    {
        return $this->api->getEncodings()->merge(
            EncodingList::fromArray($this->encoding, $this->api->getUrl()->toString())
        );
    }

    /**
     * Get supported decodings.
     *
     * @return DecodingList
     */
    protected function decodingsForCreateResource(): DecodingList
    {
        return $this->supportedDecodings();
    }

    /**
     * Get supported decodings for a specific record.
     *
     * @param mixed $record
     * @return DecodingList
     */
    protected function decodingsForResource($record): DecodingList
    {
        return $this->supportedDecodings();
    }

    /**
     * Get supported decodings for a relationship on a specific record.
     *
     * @param mixed $record
     * @param string $field
     * @return DecodingList
     */
    protected function decodingsForRelationship($record, string $field): DecodingList
    {
        return $this->supportedDecodings();
    }

    /**
     * @return DecodingList
     */
    protected function supportedDecodings(): DecodingList
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
     */
    protected function notAcceptable(AcceptHeaderInterface $header): HttpException
    {
        return new HttpException(self::HTTP_NOT_ACCEPTABLE);
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
            'The specified content type is not supported.'
        );
    }

}
