<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Api\Codec;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidJsonException;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersInterface;
use Psr\Http\Message\ServerRequestInterface;
use function CloudCreativity\LaravelJsonApi\http_contains_body;
use function CloudCreativity\LaravelJsonApi\json_decode;

/**
 * Class JsonApiRequest
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
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * @var Container
     */
    private $container;

    /**
     * @var HeaderParametersInterface|null
     */
    private $headers;

    /**
     * @var Codec|null
     */
    private $codec;

    /**
     * @var string|null
     */
    private $resourceId;

    /**
     * @var string|null
     */
    private $processId;

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
     * @param ResolverInterface $resolver
     * @param Container $container
     */
    public function __construct(Request $request, ResolverInterface $resolver, Container $container)
    {
        $this->request = $request;
        $this->resolver = $resolver;
        $this->container = $container;
    }

    /**
     * Get the content negotiation headers.
     *
     * @return HeaderParametersInterface
     */
    public function getHeaders(): HeaderParametersInterface
    {
        if ($this->headers) {
            return $this->headers;
        }

        return $this->headers = $this->container->make(HeaderParametersInterface::class);
    }

    /**
     * Set the matched codec.
     *
     * @param Codec $codec
     * @return $this
     */
    public function setCodec(Codec $codec): self
    {
        $this->codec = $codec;

        return $this;
    }

    /**
     * Get the matched codec.
     *
     * @return Codec
     */
    public function getCodec(): Codec
    {
        if (!$this->hasCodec()) {
            throw new RuntimeException('Request codec has not been matched.');
        }

        return $this->codec;
    }

    /**
     * @return bool
     */
    public function hasCodec(): bool
    {
        return !!$this->codec;
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
     * @deprecated 2.0.0
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
     * What process resource type does the request relate to?
     *
     * @return string|null
     */
    public function getProcessType(): ?string
    {
        return $this->request->route(ResourceRegistrar::PARAM_PROCESS_TYPE);
    }

    /**
     * What process id does the request relate to?
     *
     * @return string|null
     */
    public function getProcessId(): ?string
    {
        /** Cache the process id because binding substitutions will override it. */
        if (is_null($this->processId)) {
            $this->processId = $this->request->route(ResourceRegistrar::PARAM_PROCESS_ID) ?: false;
        }

        return $this->processId ?: null;
    }

    /**
     * @return AsynchronousProcess|null
     */
    public function getProcess(): ?AsynchronousProcess
    {
        $process = $this->request->route(ResourceRegistrar::PARAM_PROCESS_ID);

        return ($process instanceof AsynchronousProcess) ? $process : null;
    }

    /**
     * Get the process identifier for the request.
     *
     * @return ResourceIdentifierInterface|null
     * @deprecated 2.0.0
     */
    public function getProcessIdentifier(): ?ResourceIdentifierInterface
    {
        if (!$id = $this->getProcessId()) {
            return null;
        }

        return ResourceIdentifier::create($this->getProcessType(), $id);
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

        return $this->parameters = $this->container->make(EncodingParametersInterface::class);
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
        return $this->isMethod('get') && $this->isNotResource() && $this->isNotProcesses();
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
        return $this->isMethod('post') && $this->isNotResource();
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
     * @return bool
     */
    public function willSeeOne(): bool
    {
        return !$this->isIndex() && $this->isNotRelationship();
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
     * @return bool
     */
    public function willSeeMany(): bool
    {
        return !$this->willSeeOne();
    }

    /**
     * Is this a request to read all processes for a resource type?
     *
     * E.g. `GET /posts/queue-jobs`
     *
     * @return bool
     */
    public function isReadProcesses(): bool
    {
        return $this->isMethod('get') && $this->isProcesses() && $this->isNotProcess();
    }

    /**
     * Is this a request to read a process for a resource type?
     *
     * E.g. `GET /posts/queue-jobs/839765f4-7ff4-4625-8bf7-eecd3ab44946`
     *
     * @return bool
     */
    public function isReadProcess(): bool
    {
        return $this->isMethod('get') && $this->isProcess();
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
     * @return bool
     */
    private function isNotResource(): bool
    {
        return !$this->isResource();
    }

    /**
     * @return bool
     */
    private function isProcesses(): bool
    {
        return !empty($this->getProcessType());
    }

    /**
     * @return bool
     */
    private function isNotProcesses(): bool
    {
        return !$this->isProcesses();
    }

    /**
     * @return bool
     */
    private function isProcess(): bool
    {
        return !empty($this->getProcessId());
    }

    /**
     * @return bool
     */
    private function isNotProcess(): bool
    {
        return !$this->isProcess();
    }

    /**
     * @return bool
     */
    private function isNotRelationship(): bool
    {
        return !$this->isRelationship();
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

        $serverRequest = $this->container->make(ServerRequestInterface::class);

        /** @todo allow a Laravel request to be passed to http_contains_body */
        if (!http_contains_body($serverRequest)) {
            return false;
        }

        return json_decode($this->request->getContent());
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
