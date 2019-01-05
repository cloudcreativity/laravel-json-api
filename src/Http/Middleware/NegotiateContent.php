<?php

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\DecoderInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Http\Codec;
use CloudCreativity\LaravelJsonApi\Routing\Route;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersInterface;
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
     * @var HeaderParametersInterface
     */
    private $headers;

    /**
     * @var Route
     */
    private $route;

    /**
     * NegotiateContent constructor.
     *
     * @param Factory $factory
     * @param Api $api
     * @param HeaderParametersInterface $headers
     * @param Route $route
     */
    public function __construct(Factory $factory, Api $api, HeaderParametersInterface $headers, Route $route)
    {
        $this->factory = $factory;
        $this->api = $api;
        $this->headers = $headers;
        $this->route = $route;
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
            $this->matchCodec($request, $default),
            $decoder = $body ? $this->matchDecoder($request, $default) : null
        );

        if (!$body && $this->isExpectingContent($request)) {
            throw new DocumentRequiredException();
        }

        return $next($request);
    }

    /**
     * @param Request $request
     * @param string|null $defaultNegotiator
     * @return Codec
     */
    protected function matchCodec($request, ?string $defaultNegotiator): Codec
    {
        $negotiator = $this
            ->negotiator($this->responseResourceType(), $defaultNegotiator)
            ->withRequest($request)
            ->withDefaultCodecs($this->api->getCodecs());

        $accept = $this->headers->getAcceptHeader();

        if ($this->willSeeMany($request)) {
            return $negotiator->codecForMany($accept);
        }

        return $negotiator->codec($accept, $this->route->getResource());
    }

    /**
     * @param Request $request
     * @param string|null $defaultNegotiator
     * @return DecoderInterface|null
     */
    protected function matchDecoder($request, ?string $defaultNegotiator): ?DecoderInterface
    {
        $negotiator = $this->negotiator(
            $this->route->getResourceType(),
            $defaultNegotiator
        )->withRequest($request);

        $contentType = $this->headers->getContentTypeHeader();
        $resource = $this->route->getResource();

        if ($resource && $field = $this->route->getRelationshipName()) {
            return $negotiator->decoderForRelationship($contentType, $resource, $field);
        }

        if ($resource) {
            return $negotiator->decoderForResource($contentType, $resource);
        }

        return $negotiator->decoder($contentType);
    }

    /**
     * Get the resource type that will be in the response.
     *
     * @return string
     */
    protected function responseResourceType(): string
    {
        return $this->route->getInverseResourceType() ?: $this->route->getResourceType();
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
     * @param DecoderInterface $decoder
     * @return void
     */
    protected function matched(Codec $codec, ?DecoderInterface $decoder): void
    {
        $this->route->setCodec($codec)->setDecoder($decoder);
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        return $this->api->getContainer();
    }

    /**
     * Will the response contain a specific resource?
     *
     * E.g. for a `posts` resource, this is invoked on the following URLs:
     *
     * - `POST /posts`
     * - `GET /posts/1`
     * - `PATCH /posts/1`
     * - `DELETE /posts/1`
     *
     * I.e. a response that may contain a specified resource.
     *
     * @param Request $request
     * @return bool
     */
    public function willSeeOne($request): bool
    {
        if ($this->route->isRelationship()) {
            return false;
        }

        if ($this->route->isResource()) {
            return true;
        }

        return $request->isMethod('POST');
    }

    /**
     * Will the response contain zero-to-many of a resource?
     *
     * E.g. for a `posts` resource, this is invoked on the following URLs:
     *
     * - `/posts`
     * - `/comments/1/posts`
     *
     * I.e. a response that will contain zero to many of the posts resource.
     *
     * @param Request $request
     * @return bool
     */
    public function willSeeMany($request): bool
    {
        return !$this->willSeeOne($request);
    }

    /**
     * Is data expected for the supplied request?
     *
     * If the JSON API request is any of the following, a JSON API document
     * is expected to be set on the request:
     *
     * - Create resource
     * - Update resource
     * - Replace resource relationship
     * - Add to resource relationship
     * - Remove from resource relationship
     *
     * @param Request $request
     * @return bool
     */
    protected function isExpectingContent($request): bool
    {
        $methods = $this->route->isNotRelationship() ? ['POST', 'PATCH'] : ['POST', 'PATCH', 'DELETE'];

        return \in_array($request->getMethod(), $methods);
    }

}
