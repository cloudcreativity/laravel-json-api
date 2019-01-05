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

use CloudCreativity\LaravelJsonApi\Contracts\Http\DecoderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Http\Codec;
use CloudCreativity\LaravelJsonApi\Routing\Route;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersInterface;

/**
 * Class JsonApiRequest
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0
 */
class JsonApiRequest
{

    /**
     * @var Request
     */
    private $request;

    /**
     * @var Route
     */
    private $route;

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
     * @var DecoderInterface|null
     */
    private $decoder;

    /**
     * @var EncodingParametersInterface|null
     */
    private $parameters;

    /**
     * IlluminateRequest constructor.
     *
     * @param Request $request
     * @param Route $route
     * @param Container $container
     */
    public function __construct(
        Request $request,
        Route $route,
        Container $container
    ) {
        $this->request = $request;
        $this->route = $route;
        $this->container = $container;
    }

    /**
     * @return Route
     */
    public function getRoute(): Route
    {
        return $this->route;
    }

    /**
     * Get the domain record type that is subject of the request.
     *
     * @return string
     * @deprecated 2.0.0 use `getRoute()`
     */
    public function getType(): string
    {
        return $this->getRoute()->getType();
    }

    /**
     * What resource type does the request relate to?
     *
     * @return string|null
     *      the requested resource type, or null if none was requested.
     * @deprecated 2.0.0 use `getRoute()`
     */
    public function getResourceType(): ?string
    {
        return $this->getRoute()->getResourceType();
    }

    /**
     * What resource id does the request relate to?
     *
     * @return string|null
     * @deprecated 2.0.0 use `getRoute()`
     */
    public function getResourceId(): ?string
    {
        return $this->getRoute()->getResourceId();
    }

    /**
     * Get the resource identifier for the request.
     *
     * @return ResourceIdentifierInterface|null
     * @deprecated 2.0.0
     */
    public function getResourceIdentifier(): ?ResourceIdentifierInterface
    {
        return $this->getRoute()->getResourceIdentifier();
    }

    /**
     * Get the domain object that the request relates to.
     *
     * @return mixed|null
     * @deprecated 2.0.0 use `getRoute()`
     */
    public function getResource()
    {
        return $this->getRoute()->getResource();
    }

    /**
     * What resource relationship does the request relate to?
     *
     * @return string|null
     * @deprecated 2.0.0 use `getRoute()`
     */
    public function getRelationshipName(): ?string
    {
        return $this->getRoute()->getRelationshipName();
    }

    /**
     * What is the inverse resource type for a relationship?
     *
     * For example, a `GET /posts/1/author`, the string returned by this method
     * would be `users` if the related author is a `users` JSON API resource type.
     *
     * @return string|null
     * @deprecated 2.0.0 use `getRoute()`
     */
    public function getInverseResourceType(): ?string
    {
        return $this->getRoute()->getInverseResourceType();
    }

    /**
     * What process resource type does the request relate to?
     *
     * @return string|null
     * @deprecated 2.0.0 use `getRoute()`
     */
    public function getProcessType(): ?string
    {
        return $this->getRoute()->getProcessType();
    }

    /**
     * What process id does the request relate to?
     *
     * @return string|null
     * @deprecated 2.0.0 use `getRoute()`
     */
    public function getProcessId(): ?string
    {
        return $this->getRoute()->getProcessId();
    }

    /**
     * @return AsynchronousProcess|null
     * @deprecated 2.0.0 use `getRoute()`
     */
    public function getProcess(): ?AsynchronousProcess
    {
        return $this->getRoute()->getProcess();
    }

    /**
     * Get the process identifier for the request.
     *
     * @return ResourceIdentifierInterface|null
     * @deprecated 2.0.0
     */
    public function getProcessIdentifier(): ?ResourceIdentifierInterface
    {
        return $this->getRoute()->getProcessIdentifier();
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
     * Is this an index request?
     *
     * E.g. `GET /posts`
     *
     * @return bool
     */
    public function isIndex(): bool
    {
        return $this->isMethod('get') &&
            $this->getRoute()->isNotResource() &&
            $this->getRoute()->isNotProcesses();
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
        return $this->isMethod('post') && $this->getRoute()->isNotResource();
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
        return $this->isMethod('get') &&
            $this->getRoute()->isResource() &&
            $this->getRoute()->isNotRelationship();
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
        return $this->isMethod('patch') &&
            $this->getRoute()->isResource() &&
            $this->getRoute()->isNotRelationship();
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
        return $this->isMethod('delete') &&
            $this->getRoute()->isResource() &&
            $this->getRoute()->isNotRelationship();
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
        return $this->getRoute()->isRelationship() && !$this->hasRelationships();
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
     * Is this a request to read all processes for a resource type?
     *
     * E.g. `GET /posts/queue-jobs`
     *
     * @return bool
     */
    public function isReadProcesses(): bool
    {
        return $this->isMethod('get') &&
            $this->getRoute()->isProcesses() &&
            $this->getRoute()->isNotProcess();
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
        return $this->isMethod('get') && $this->getRoute()->isProcess();
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
        return $this->request->isMethod($method);
    }

}
