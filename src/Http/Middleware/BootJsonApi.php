<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

use Closure;
use CloudCreativity\JsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\InboundRequestInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\JsonApi\Exceptions\InvalidJsonException;
use CloudCreativity\JsonApi\Object\Document;
use CloudCreativity\JsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Repository;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Illuminate\Pagination\AbstractPaginator;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Http\Message\ServerRequestInterface;
use function CloudCreativity\LaravelJsonApi\http_contains_body;

/**
 * Class BootJsonApi
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class BootJsonApi
{

    /**
     * @var Container
     */
    private $container;

    /**
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * Start JSON API support.
     *
     * This middleware:
     * - Loads the configuration for the named API that this request is being routed to.
     * - Registers the API in the service container.
     * - Triggers client/server content negotiation as per the JSON API spec.
     *
     * @param Request $request
     * @param Closure $next
     * @param $namespace
     *      the API namespace, as per your JSON API configuration.
     * @return mixed
     */
    public function handle($request, Closure $next, $namespace)
    {
        /** @var Factory $factory */
        $factory = $this->container->make(Factory::class);
        /** @var ServerRequestInterface $request */
        $serverRequest = $this->container->make(ServerRequestInterface::class);

        /** Build and register the API */
        $api = $this->bindApi($namespace, $request->getSchemeAndHttpHost());

        /** Build and register the JSON API inbound request */
        $this->bindRequest($factory, $serverRequest, $request, $api);

        /** Set up the Laravel paginator to read from JSON API request instead */
        $this->bindPageResolver();

        return $next($request);
    }

    /**
     * Build the API instance and bind it into the container.
     *
     * @param $namespace
     * @param $host
     * @return Api
     */
    protected function bindApi($namespace, $host)
    {
        /** @var Repository $repository */
        $repository = $this->container->make(Repository::class);

        $api = $repository->createApi($namespace, $host);
        $this->container->instance(Api::class, $api);
        $this->container->alias(Api::class, 'json-api.inbound');

        return $api;
    }

    /**
     * @param FactoryInterface $factory
     * @param ServerRequestInterface $serverRequest
     * @param Request $httpRequest
     * @param Api $api
     * @return void
     */
    protected function bindRequest(
        FactoryInterface $factory,
        ServerRequestInterface $serverRequest,
        Request $httpRequest,
        Api $api
    ) {
        $inboundRequest = $this->parseServerRequest(
            $serverRequest,
            $factory,
            $api->getStore(),
            $api->getCodecMatcher(),
            $httpRequest->route(ResourceRegistrar::PARAM_RESOURCE_TYPE),
            $httpRequest->route(ResourceRegistrar::PARAM_RESOURCE_ID),
            $httpRequest->route(ResourceRegistrar::PARAM_RELATIONSHIP_NAME),
            $httpRequest->is('*/relationships/*')
        );

        $this->container->instance(InboundRequestInterface::class, $inboundRequest);
        $this->container->alias(InboundRequestInterface::class, 'json-api.request');
    }

    /**
     * Override the page resolver to read the page parameter from the JSON API request.
     *
     * @return void
     */
    protected function bindPageResolver()
    {
        /** Override the current page resolution */
        AbstractPaginator::currentPageResolver(function ($pageName) {
            /** @var InboundRequestInterface $request */
            $request = app(InboundRequestInterface::class);
            $pagination = (array) $request->getParameters()->getPaginationParameters();

            return isset($pagination[$pageName]) ? $pagination[$pageName] : null;
        });
    }

    /**
     * Perform content negotiation.
     *
     * @param HttpFactoryInterface $httpFactory
     * @param ServerRequestInterface $request
     * @param CodecMatcherInterface $codecMatcher
     * @throws JsonApiException
     * @see http://jsonapi.org/format/#content-negotiation
     */
    protected function doContentNegotiation(
        HttpFactoryInterface $httpFactory,
        ServerRequestInterface $request,
        CodecMatcherInterface $codecMatcher
    ) {
        $parser = $httpFactory->createHeaderParametersParser();
        $checker = $httpFactory->createHeadersChecker($codecMatcher);

        $checker->checkHeaders($parser->parse($request, http_contains_body($request)));
    }


    /**
     * Parse a server request into a JSON API inbound request object.
     *
     * This method will parse a server request into an inbound JSON API request
     * object that describes the type of JSON API request according to the spec's
     * definitions. It will throw a JSON API exception in any of the following
     * circumstances:
     *
     * - There is a resource id and the type/id combination do not exist (404).
     * - The request fails content negotiation (406/415).
     * - The HTTP request body content does not decode as JSON (400).
     * - The HTTP request body does not contain a JSON API document but one is expected
     * for the request (400).
     * - Any of the JSON API query parameters do not comply with the spec (400).
     *
     * This method expects information about the URL (resource type, id, etc)
     * to be provided as different frameworks may have access to this information
     * separately from the URL. E.g. some frameworks allow variables to be defined
     * on the route definition, or the variables may be available as route parameters.
     * As such, there is no provision for parsing the server request's URL into
     * the parameters, as it will always be more efficient to obtain these directly
     * from the framework-specific implementation.
     *
     * @param ServerRequestInterface $serverRequest
     * @param FactoryInterface $factory
     * @param StoreInterface $store
     * @param CodecMatcherInterface $codecMatcher
     * @param string $resourceType
     * @param string|null $resourceId
     * @param string|null $relationshipName
     * @param bool $relationships
     * @return InboundRequestInterface
     * @throws JsonApiException
     */
    protected function parseServerRequest(
        ServerRequestInterface $serverRequest,
        FactoryInterface $factory,
        StoreInterface $store,
        CodecMatcherInterface $codecMatcher,
        $resourceType,
        $resourceId,
        $relationshipName,
        $relationships
    ) {
        /** If the resource id is not valid, throw a 404 exception. */
        if ($resourceId && !$this->doesResourceExist($store, $resourceType, $resourceId)) {
            throw new JsonApiException([], 404);
        }

        /** Do content negotiation. */
        $this->doContentNegotiation($factory, $serverRequest, $codecMatcher);

        /** Parse the server request into a JSON API inbound request object. */
        $inboundRequest = $factory->createInboundRequest(
            $serverRequest->getMethod(),
            $resourceType,
            $resourceId,
            $relationshipName,
            $relationships,
            $this->decodeDocument($serverRequest),
            $this->parseQueryParameters($serverRequest, $factory->createQueryParametersParser())
        );

        /** Check that there is a JSON API document if one is required for the type of request. */
        if ($this->isExpectingDocument($inboundRequest) && !$inboundRequest->getDocument()) {
            throw new DocumentRequiredException();
        }

        return $inboundRequest;
    }

    /**
     * Is the supplied resource type and id valid?
     *
     * @param StoreInterface $store
     * @param $resourceType
     * @param $resourceId
     * @return bool
     */
    protected function doesResourceExist(StoreInterface $store, $resourceType, $resourceId)
    {
        $identifier = ResourceIdentifier::create($resourceType, $resourceId);

        return $store->exists($identifier);
    }

    /**
     * Extract the JSON API document from the request.
     *
     * @param ServerRequestInterface $serverRequest
     * @return Document|null
     * @throws InvalidJsonException
     */
    protected function decodeDocument(ServerRequestInterface $serverRequest)
    {
        if (!http_contains_body($serverRequest)) {
            return null;
        }

        return new Document(json_decode((string) $serverRequest->getBody()));
    }

    /**
     * Extract the JSON API query parameters from the request.
     *
     * @param ServerRequestInterface $serverRequest
     * @param QueryParametersParserInterface $parser
     * @return EncodingParametersInterface
     */
    protected function parseQueryParameters(
        ServerRequestInterface $serverRequest,
        QueryParametersParserInterface $parser
    ) {
        return $parser->parseQueryParameters($serverRequest->getQueryParams());
    }

    /**
     * Is a document expected for the supplied request?
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
     * @param InboundRequestInterface $request
     * @return bool
     */
    protected function isExpectingDocument(InboundRequestInterface $request)
    {
        return $request->isCreateResource() ||
            $request->isUpdateResource() ||
            $request->isReplaceRelationship() ||
            $request->isAddToRelationship() ||
            $request->isRemoveFromRelationship();
    }
}
