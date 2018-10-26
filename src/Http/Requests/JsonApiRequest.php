<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidJsonException;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use function CloudCreativity\LaravelJsonApi\http_contains_body;
use function CloudCreativity\LaravelJsonApi\json_decode;

/**
 * Class IlluminateRequest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiRequest
{

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ServerRequestInterface
     */
    private $serverRequest;

    /**
     * @var HttpFactoryInterface
     */
    private $factory;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * @var string|null
     */
    private $resourceId;

    /**
     * @var object|bool|null
     */
    private $document;

    /**
     * @var EncodingParametersInterface|null
     */
    private $parameters;

    /**
     * IlluminateRequest constructor.
     *
     * @param Request $request
     * @param ServerRequestInterface $serverRequest
     * @param ResolverInterface $resolver
     * @param HttpFactoryInterface $factory
     */
    public function __construct(
        Request $request,
        ServerRequestInterface $serverRequest,
        ResolverInterface $resolver,
        HttpFactoryInterface $factory
    ) {
        $this->request = $request;
        $this->serverRequest = $serverRequest;
        $this->resolver = $resolver;
        $this->factory = $factory;
    }

    /**
     * Get the domain record type that is subject of the request.
     *
     * @return string
     */
    public function getType(): string
    {
        if ($resource = $this->getResource()) {
            return get_class($resource);
        }

        $resourceType = $this->getResourceType();

        if (!$type = $this->resolver->getType($resourceType)) {
            throw new RuntimeException("JSON API resource type {$resourceType} is not registered.");
        }

        return $type;
    }

    /**
     * What resource type does the request relate to?
     *
     * @return string|null
     *      the requested resource type, or null if none was requested.
     */
    public function getResourceType(): ?string
    {
        return $this->request->route(ResourceRegistrar::PARAM_RESOURCE_TYPE);
    }

    /**
     * What resource id does the request relate to?
     *
     * @return string|null
     */
    public function getResourceId(): ?string
    {
        /** Cache the resource id because binding substitutions will override it. */
        if (is_null($this->resourceId)) {
            $this->resourceId = $this->request->route(ResourceRegistrar::PARAM_RESOURCE_ID) ?: false;
        }

        return $this->resourceId ?: null;
    }

    /**
     * Get the resource identifier for the request.
     *
     * @return ResourceIdentifierInterface|null
     */
    public function getResourceIdentifier(): ?ResourceIdentifierInterface
    {
        if (!$resourceId = $this->getResourceId()) {
            return null;
        }

        return ResourceIdentifier::create($this->getResourceType(), $resourceId);
    }

    /**
     * Get the domain object that the request relates to.
     *
     * @return mixed|null
     */
    public function getResource()
    {
        $resource = $this->request->route(ResourceRegistrar::PARAM_RESOURCE_ID);

        return is_object($resource) ? $resource : null;
    }

    /**
     * What resource relationship does the request relate to?
     *
     * @return string|null
     */
    public function getRelationshipName(): ?string
    {
        return $this->request->route(ResourceRegistrar::PARAM_RELATIONSHIP_NAME);
    }

    /**
     * What is the inverse resource type for a relationship?
     *
     * For example, a `GET /posts/1/author`, the string returned by this method
     * would be `users` if the related author is a `users` JSON API resource type.
     *
     * @return string|null
     */
    public function getInverseResourceType(): ?string
    {
        return $this->request->route(ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE);
    }

    /**
     * Get the encoding parameters from the request.
     *
     * @return EncodingParametersInterface
     */
    public function getParameters(): EncodingParametersInterface
    {
        if ($this->parameters) {
            return $this->parameters;
        }

        return $this->parameters = $this->parseParameters();
    }

    /**
     * Get the JSON API document from the request, if there is one.
     *
     * @return object|null
     */
    public function getDocument()
    {
        if (is_null($this->document)) {
            $this->document = $this->decodeDocument();
        }

        return $this->document ? clone $this->document : null;
    }

    /**
     * Is this an index request?
     *
     * E.g. `GET /posts`
     *
     * @return bool
     */
    public function isIndex(): bool
    {
        return $this->isMethod('get') && !$this->isResource();
    }

    /**
     * Is this a create resource request?
     *
     * E.g. `POST /posts`
     *
     * @return bool
     */
    public function isCreateResource(): bool
    {
        return $this->isMethod('post') && !$this->isResource();
    }

    /**
     * Is this a read resource request?
     *
     * E.g. `GET /posts/1`
     *
     * @return bool
     */
    public function isReadResource(): bool
    {
        return $this->isMethod('get') && $this->isResource() && !$this->isRelationship();
    }

    /**
     * Is this an update resource request?
     *
     * E.g. `PATCH /posts/1`
     *
     * @return bool
     */
    public function isUpdateResource(): bool
    {
        return $this->isMethod('patch') && $this->isResource() && !$this->isRelationship();
    }

    /**
     * Is this a delete resource request?
     *
     * E.g. `DELETE /posts/1`
     *
     * @return bool
     */
    public function isDeleteResource(): bool
    {
        return $this->isMethod('delete') && $this->isResource() && !$this->isRelationship();
    }

    /**
     * Is this a request for a related resource or resources?
     *
     * E.g. `GET /posts/1/author` or `GET /posts/1/comments`
     *
     * @return bool
     */
    public function isReadRelatedResource(): bool
    {
        return $this->isRelationship() && !$this->hasRelationships();
    }

    /**
     * Does the request URI have the 'relationships' keyword?
     *
     * E.g. `/posts/1/relationships/author` or `/posts/1/relationships/comments`
     *
     * I.e. the URL request contains the pattern `/relationships/` after the
     * resource id and before the relationship name.
     *
     * @return bool
     * @see http://jsonapi.org/format/#fetching-relationships
     */
    public function hasRelationships(): bool
    {
        return $this->request->is('*/relationships/*');
    }

    /**
     * Is this a request to read the data of a relationship?
     *
     * E.g. `GET /posts/1/relationships/author` or `GET /posts/1/relationships/comments`
     *
     * @return bool
     */
    public function isReadRelationship(): bool
    {
        return $this->isMethod('get') && $this->hasRelationships();
    }

    /**
     * Is this a request to modify the data of a relationship?
     *
     * I.e. is this a replace relationship, add to relationship or remove from relationship
     * request.
     *
     * @return bool
     */
    public function isModifyRelationship(): bool
    {
        return $this->isReplaceRelationship() ||
            $this->isAddToRelationship() ||
            $this->isRemoveFromRelationship();
    }

    /**
     * Is this a request to replace the data of a relationship?
     *
     * E.g. `PATCH /posts/1/relationships/author` or `PATCH /posts/1/relationships/comments`
     */
    public function isReplaceRelationship(): bool
    {
        return $this->isMethod('patch') && $this->hasRelationships();
    }

    /**
     * Is this a request to add to the data of a has-many relationship?
     *
     * E.g. `POST /posts/1/relationships/comments`
     *
     * @return bool
     */
    public function isAddToRelationship(): bool
    {
        return $this->isMethod('post') && $this->hasRelationships();
    }

    /**
     * Is this a request to remove from the data of a has-many relationship?
     *
     * E.g. `DELETE /posts/1/relationships/comments`
     *
     * @return bool
     */
    public function isRemoveFromRelationship(): bool
    {
        return $this->isMethod('delete') && $this->hasRelationships();
    }

    /**
     * @return bool
     */
    private function isResource(): bool
    {
        return !empty($this->getResourceId());
    }

    /**
     * @return bool
     */
    private function isRelationship(): bool
    {
        return !empty($this->getRelationshipName());
    }

    /**
     * Is the HTTP request method the one provided?
     *
     * @param string $method
     *      the expected method - case insensitive.
     * @return bool
     */
    private function isMethod($method): bool
    {
        return strtoupper($this->request->method()) === strtoupper($method);
    }

    /**
     * Extract the JSON API document from the request.
     *
     * @return object|false
     * @throws InvalidJsonException
     */
    private function decodeDocument()
    {
        if (!$this->expectsData()) {
            return false;
        }

        if (!http_contains_body($this->serverRequest)) {
            return false;
        }

        return json_decode((string) $this->serverRequest->getBody());
    }

    /**
     * @return EncodingParametersInterface
     */
    private function parseParameters(): EncodingParametersInterface
    {
        $parser = $this->factory->createQueryParametersParser();

        return $parser->parseQueryParameters($this->serverRequest->getQueryParams());
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
     * @return bool
     */
    private function expectsData(): bool
    {
        return $this->isCreateResource() ||
            $this->isUpdateResource() ||
            $this->isReplaceRelationship() ||
            $this->isAddToRelationship() ||
            $this->isRemoveFromRelationship();
    }

}
