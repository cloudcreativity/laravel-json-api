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

use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestInterface;
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
class IlluminateRequest implements RequestInterface
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
     * @inheritDoc
     */
    public function getType()
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
     * @inheritdoc
     */
    public function getResourceType()
    {
        return $this->request->route(ResourceRegistrar::PARAM_RESOURCE_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function getResourceId()
    {
        /** Cache the resource id because binding substitutions will override it. */
        if (is_null($this->resourceId)) {
            $this->resourceId = $this->request->route(ResourceRegistrar::PARAM_RESOURCE_ID) ?: false;
        }

        return $this->resourceId ?: null;
    }

    /**
     * @inheritdoc
     */
    public function getResourceIdentifier()
    {
        if (!$resourceId = $this->getResourceId()) {
            return null;
        }

        return ResourceIdentifier::create($this->getResourceType(), $resourceId);
    }

    /**
     * @inheritdoc
     */
    public function getResource()
    {
        $resource = $this->request->route(ResourceRegistrar::PARAM_RESOURCE_ID);

        return is_object($resource) ? $resource : null;
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipName()
    {
        return $this->request->route(ResourceRegistrar::PARAM_RELATIONSHIP_NAME);
    }

    /**
     * @inheritdoc
     */
    public function getInverseResourceType()
    {
        return $this->request->route(ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE);
    }

    /**
     * @inheritdoc
     */
    public function getParameters()
    {
        if ($this->parameters) {
            return $this->parameters;
        }

        return $this->parameters = $this->parseParameters();
    }

    /**
     * @inheritdoc
     */
    public function getDocument()
    {
        if (is_null($this->document)) {
            $this->document = $this->decodeDocument();
        }

        return $this->document ? clone $this->document : null;
    }

    /**
     * @inheritdoc
     */
    public function isIndex()
    {
        return $this->isMethod('get') && !$this->isResource();
    }

    /**
     * @inheritdoc
     */
    public function isCreateResource()
    {
        return $this->isMethod('post') && !$this->isResource();
    }

    /**
     * @inheritdoc
     */
    public function isReadResource()
    {
        return $this->isMethod('get') && $this->isResource() && !$this->isRelationship();
    }

    /**
     * @inheritdoc
     */
    public function isUpdateResource()
    {
        return $this->isMethod('patch') && $this->isResource() && !$this->isRelationship();
    }

    /**
     * @inheritdoc
     */
    public function isDeleteResource()
    {
        return $this->isMethod('delete') && $this->isResource() && !$this->isRelationship();
    }

    /**
     * @inheritdoc
     */
    public function isReadRelatedResource()
    {
        return $this->isRelationship() && !$this->hasRelationships();
    }

    /**
     * @inheritdoc
     */
    public function hasRelationships()
    {
        return $this->request->is('*/relationships/*');
    }

    /**
     * @inheritdoc
     */
    public function isReadRelationship()
    {
        return $this->isMethod('get') && $this->hasRelationships();
    }

    /**
     * @inheritdoc
     */
    public function isModifyRelationship()
    {
        return $this->isReplaceRelationship() ||
            $this->isAddToRelationship() ||
            $this->isRemoveFromRelationship();
    }

    /**
     * @inheritdoc
     */
    public function isReplaceRelationship()
    {
        return $this->isMethod('patch') && $this->hasRelationships();
    }

    /**
     * @inheritdoc
     */
    public function isAddToRelationship()
    {
        return $this->isMethod('post') && $this->hasRelationships();
    }

    /**
     * @inheritdoc
     */
    public function isRemoveFromRelationship()
    {
        return $this->isMethod('delete') && $this->hasRelationships();
    }

    /**
     * @return bool
     */
    private function isResource()
    {
        return !empty($this->getResourceId());
    }

    /**
     * @return bool
     */
    private function isRelationship()
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
    private function isMethod($method)
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
    private function parseParameters()
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
    private function expectsData()
    {
        return $this->isCreateResource() ||
            $this->isUpdateResource() ||
            $this->isReplaceRelationship() ||
            $this->isAddToRelationship() ||
            $this->isRemoveFromRelationship();
    }

}
