<?php

namespace CloudCreativity\LaravelJsonApi\Http;

use CloudCreativity\LaravelJsonApi\Api\Codec;
use CloudCreativity\LaravelJsonApi\Api\Codecs;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\DecoderInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ContentNegotiator implements ContentNegotiatorInterface
{

    /**
     * @var Request
     */
    protected $request;

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
    public function codec(AcceptHeaderInterface $header, Codecs $codecs, $record = null): Codec
    {
        if ($record) {
            $codecs = $this->codecsFor($codecs, $record);
        }

        return $this->checkAcceptTypes($header, $codecs);
    }

    /**
     * @inheritDoc
     */
    public function codecForMany(AcceptHeaderInterface $header, Codecs $codecs): Codec
    {
        return $this->checkAcceptTypes($header, $codecs);
    }

    /**
     * @inheritdoc
     */
    public function decoder(HeaderInterface $header): DecoderInterface
    {
        return $this->checkContentType($header, $this->decoders());
    }

    /**
     * @inheritDoc
     */
    public function decoderForResource(HeaderInterface $header, $record): DecoderInterface
    {
        return $this->checkContentType(
            $header,
            $this->resourceDecoders($record)
        );
    }

    /**
     * @inheritDoc
     */
    public function decoderForRelationship(HeaderInterface $header, $record, string $field): DecoderInterface
    {
        return $this->checkContentType(
            $header,
            $this->relationshipDecoders($record, $field)
        );
    }

    /**
     * Get codecs for the supplied record.
     *
     * Child classes can overload this method if they want to add codecs.
     *
     * @param Codecs $defaultCodecs
     *      the API's default codecs.
     * @param mixed $record
     * @return Codecs
     */
    protected function codecsFor(Codecs $defaultCodecs, $record): Codecs
    {
        return $defaultCodecs;
    }

    /**
     * Get decoders.
     *
     * @return Decoders
     */
    protected function decoders(): Decoders
    {
        return $this->defaultDecoders();
    }

    /**
     * Get decoders for a specific record.
     *
     * @param mixed $record
     * @return Decoders
     */
    protected function resourceDecoders($record): Decoders
    {
        return $this->defaultDecoders();
    }

    /**
     * Get decoders for a relationship on a specific record.
     *
     * @param mixed $record
     * @param string $field
     * @return Decoders
     */
    protected function relationshipDecoders($record, string $field): Decoders
    {
        return $this->defaultDecoders();
    }

    /**
     * @return Decoders
     */
    protected function defaultDecoders(): Decoders
    {
        return new Decoders($this->factory->createDecoder());
    }

    /**
     * @param AcceptHeaderInterface $header
     * @param Codecs $codecs
     * @return Codec
     * @throws HttpException
     */
    protected function checkAcceptTypes(AcceptHeaderInterface $header, Codecs $codecs): Codec
    {
        if (!$codec = $codecs->acceptable($header)) {
            throw $this->notAcceptable($header);
        }

        return $codec;
    }

    /**
     * @param HeaderInterface $header
     * @param Decoders $decoders
     * @return DecoderInterface
     * @throws HttpException
     */
    protected function checkContentType(HeaderInterface $header, Decoders $decoders): DecoderInterface
    {
        if (!$decoder = $decoders->forHeader($header)) {
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
