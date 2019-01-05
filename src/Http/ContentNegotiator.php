<?php

namespace CloudCreativity\LaravelJsonApi\Http;

use CloudCreativity\LaravelJsonApi\Api\Codec;
use CloudCreativity\LaravelJsonApi\Api\Codecs;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\MediaTypeInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ContentNegotiator implements ContentNegotiatorInterface
{

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
    public function decoder(HeaderInterface $header, $record = null): Decoder
    {
        if ($this->isNotJsonApi($header)) {
            throw $this->unsupportedMediaType();
        }

        return new Decoder();
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
