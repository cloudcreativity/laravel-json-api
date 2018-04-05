<?php

namespace CloudCreativity\JsonApi\Http\Middleware;

use CloudCreativity\JsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\Requests\InboundRequestInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\JsonApi\Exceptions\InvalidJsonException;
use CloudCreativity\JsonApi\Object\Document;
use CloudCreativity\JsonApi\Object\ResourceIdentifier;
use Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Http\Message\ServerRequestInterface;
use function CloudCreativity\JsonApi\http_contains_body;
use function CloudCreativity\JsonApi\json_decode;

/**
 * Trait ParsesServerRequests
 *
 * Provides utility functions for middleware responsible for parsing a server
 * request into the JSON API inbound request object. This inbound request
 * object holds information about the request that determines what type of
 * JSON API request it is. It also holds the JSON API query parameters for
 * the HTTP request, plus the JSON API document (request content) if there
 * is one.
 *
 * @package CloudCreativity\JsonApi
 */
trait ParsesServerRequests
{

    use NegotiatesContent;

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
