<?php

namespace CloudCreativity\LaravelJsonApi\Http;

use CloudCreativity\LaravelJsonApi\Api\Codec;
use CloudCreativity\LaravelJsonApi\Api\Codecs;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\LaravelJsonApi\Utils\Helpers;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ContentNegotiator implements ContentNegotiatorInterface
{

    /**
     * @var HeaderParametersInterface
     */
    private $headers;

    /**
     * ContentNegotiator constructor.
     *
     * @param HeaderParametersInterface $headers
     */
    public function __construct(HeaderParametersInterface $headers)
    {
        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    public function negotiate(Codecs $codecs, $request, $record = null): Codec
    {
        return $this->checkHeaders(
            $this->headers->getAcceptHeader(),
            $this->headers->getContentTypeHeader(),
            $codecs,
            $request
        );
    }

    /**
     * @inheritDoc
     */
    public function negotiateMany(Codecs $codecs, $request): Codec
    {
        return $this->checkHeaders(
            $this->headers->getAcceptHeader(),
            $this->headers->getContentTypeHeader(),
            $codecs,
            $request
        );
    }

    /**
     * @param AcceptHeaderInterface $accept
     * @param HeaderInterface|null $contentType
     * @param Codecs $codecs
     * @param $request
     * @return Codec
     */
    protected function checkHeaders(
        AcceptHeaderInterface $accept,
        ?HeaderInterface $contentType,
        Codecs $codecs,
        $request
    ): Codec
    {
        $codec = $this->checkAcceptTypes($accept, $codecs);

        if ($contentType) {
            $this->checkContentType($request);
        }

        return $codec;
    }

    /**
     * @param AcceptHeaderInterface $header
     * @param Codecs $codecs
     * @return Codec
     * @throws HttpException
     */
    protected function checkAcceptTypes(AcceptHeaderInterface $header, Codecs $codecs): Codec
    {
        if (!$codec = $this->accept($header, $codecs)) {
            throw $this->notAcceptable($header);
        }

        return $codec;
    }


    /**
     * @param Request $request
     * @return void
     * @throws HttpException
     */
    protected function checkContentType($request): void
    {
        if (!$this->isJsonApi($request)) {
            throw $this->unsupportedMediaType();
        }
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
     * @param AcceptHeaderInterface $header
     * @param Codecs $codecs
     * @return Codec|null
     */
    protected function accept(AcceptHeaderInterface $header, Codecs $codecs): ?Codec
    {
        return $codecs->acceptable($header);
    }

    /**
     * Has the request sent JSON API content?
     *
     * @param $request
     * @return bool
     */
    protected function isJsonApi($request): bool
    {
        return Helpers::isJsonApi($request);
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
