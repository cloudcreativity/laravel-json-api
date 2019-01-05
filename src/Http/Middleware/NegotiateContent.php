<?php

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Codec;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Decoder;
use CloudCreativity\LaravelJsonApi\Http\Requests\JsonApiRequest;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use function CloudCreativity\LaravelJsonApi\http_contains_body;

class NegotiateContent
{

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var Api
     */
    private $api;

    /**
     * @var JsonApiRequest
     */
    private $jsonApiRequest;

    /**
     * NegotiateContent constructor.
     *
     * @param Factory $factory
     * @param Api $api
     * @param JsonApiRequest $request
     */
    public function __construct(Factory $factory, Api $api, JsonApiRequest $request)
    {
        $this->factory = $factory;
        $this->api = $api;
        $this->jsonApiRequest = $request;
    }

    /**
     * Handle the request.
     *
     * @param Request $request
     * @param \Closure $next
     * @param string|null $default
     *      the default negotiator to use if there is not one for the resource type.
     * @return mixed
     * @throws HttpException
     */
    public function handle($request, \Closure $next, string $default = null)
    {
        $body = http_contains_body($request);

        $this->matched(
            $this->matchCodec($default),
            $decoder = $body ? $this->matchDecoder($default) : null
        );

        if (!$body && $this->jsonApiRequest->isExpectingContent()) {
            throw new DocumentRequiredException();
        }

        return $next($request);
    }

    /**
     * @param string|null $defaultNegotiator
     * @return Codec
     */
    protected function matchCodec(?string $defaultNegotiator): Codec
    {
        $negotiator = $this->negotiator($this->responseResourceType(), $defaultNegotiator);
        $accept = $this->jsonApiRequest->getHeaders()->getAcceptHeader();
        $codecs = $this->api->getCodecs();

        if ($this->jsonApiRequest->willSeeMany()) {
            return $negotiator->codecForMany($accept, $codecs);
        }

        return $negotiator->codec($accept, $codecs, $this->jsonApiRequest->getResource());
    }

    /**
     * @param string|null $defaultNegotiator
     * @return Decoder|null
     */
    protected function matchDecoder(?string $defaultNegotiator): ?Decoder
    {
        $negotiator = $this->negotiator(
            $this->jsonApiRequest->getResourceType(),
            $defaultNegotiator
        );

        return $negotiator->decoder(
            $this->jsonApiRequest->getHeaders()->getContentTypeHeader(),
            $this->jsonApiRequest->getResource()
        );
    }

    /**
     * Get the resource type that will be in the response.
     *
     * @return string
     */
    protected function responseResourceType(): string
    {
        return $this->jsonApiRequest->getInverseResourceType() ?: $this->jsonApiRequest->getResourceType();
    }

    /**
     * @param string $resourceType
     * @param string|null $default
     * @return ContentNegotiatorInterface
     */
    protected function negotiator(string $resourceType, ?string $default): ContentNegotiatorInterface
    {
        if ($negotiator = $this->getContainer()->getContentNegotiatorByResourceType($resourceType)) {
            return $negotiator;
        }

        if ($default) {
            return $this->getContainer()->getContentNegotiatorByName($default);
        }

        return $this->defaultNegotiator();
    }

    /**
     * Get the default content negotiator.
     *
     * @return ContentNegotiatorInterface
     */
    protected function defaultNegotiator(): ContentNegotiatorInterface
    {
        return $this->factory->createContentNegotiator();
    }

    /**
     * Apply the matched codec.
     *
     * @param Codec $codec
     * @param Decoder $decoder
     * @return void
     */
    protected function matched(Codec $codec, ?Decoder $decoder): void
    {
        $this->jsonApiRequest
            ->setCodec($codec)
            ->setDecoder($decoder);
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->api->getContainer();
    }

}
