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

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Codec\Codec;
use CloudCreativity\LaravelJsonApi\Codec\Decoding;
use CloudCreativity\LaravelJsonApi\Codec\Encoding;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderParametersInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Routing\Route;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class NegotiateContent
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class NegotiateContent
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var Route
     */
    private $route;

    /**
     * NegotiateContent constructor.
     *
     * @param Container $container
     * @param Factory $factory
     * @param Route $route
     */
    public function __construct(Container $container, Factory $factory, Route $route)
    {
        $this->container = $container;
        $this->factory = $factory;
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
        $api = $this->container->make(Api::class);
        /** @var HeaderParametersInterface $headers */
        $headers = $this->container->make(HeaderParametersInterface::class);
        $contentType = $headers->getContentTypeHeader();

        $codec = $this->factory->createCodec(
            $api->getContainer(),
            $this->matchEncoding($api, $request, $headers->getAcceptHeader(), $default),
            $decoder = $contentType ? $this->matchDecoder($api, $request, $contentType, $default) : null
        );

        $this->matched($codec);

        if (!$contentType && $this->isExpectingContent($request)) {
            throw new DocumentRequiredException();
        }

        return $next($request);
    }

    /**
     * @param Api $api
     * @param Request $request
     * @param AcceptHeaderInterface $accept
     * @param string|null $defaultNegotiator
     * @return Encoding
     */
    protected function matchEncoding(
        Api $api,
        $request,
        AcceptHeaderInterface $accept,
        ?string $defaultNegotiator
    ): Encoding
    {
        $negotiator = $this
            ->negotiator($api->getContainer(), $this->responseResourceType(), $defaultNegotiator)
            ->withRequest($request)
            ->withApi($api);

        if ($this->willSeeMany($request)) {
            return $negotiator->encodingForMany($accept);
        }

        return $negotiator->encoding($accept, $this->route->getResource());
    }

    /**
     * @param Api $api
     * @param Request $request
     * @param HeaderInterface $contentType
     * @param string|null $defaultNegotiator
     * @return Decoding|null
     */
    protected function matchDecoder(
        Api $api,
        $request,
        HeaderInterface $contentType,
        ?string $defaultNegotiator
    ): ?Decoding
    {
        $negotiator = $this
            ->negotiator($api->getContainer(), $this->route->getResourceType(), $defaultNegotiator)
            ->withRequest($request)
            ->withApi($api);

        $resource = $this->route->getResource();

        if ($resource && $field = $this->route->getRelationshipName()) {
            return $negotiator->decodingForRelationship($contentType, $resource, $field);
        }

        return $negotiator->decoding($contentType, $resource);
    }

    /**
     * Get the resource type that will be in the response.
     *
     * @return string|null
     */
    protected function responseResourceType(): ?string
    {
        return $this->route->getInverseResourceType() ?: $this->route->getResourceType();
    }

    /**
     * @param ContainerInterface $container
     * @param string|null $resourceType
     * @param string|null $default
     * @return ContentNegotiatorInterface
     */
    protected function negotiator(
        ContainerInterface $container,
        ?string $resourceType,
        ?string $default
    ): ContentNegotiatorInterface
    {
        if ($resourceType && $negotiator = $container->getContentNegotiatorByResourceType($resourceType)) {
            return $negotiator;
        }

        if ($default) {
            return $container->getContentNegotiatorByName($default);
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
     */
    protected function matched(Codec $codec): void
    {
        $this->route->setCodec($codec);
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
