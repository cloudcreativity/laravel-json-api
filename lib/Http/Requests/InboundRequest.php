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

namespace CloudCreativity\JsonApi\Http\Requests;

use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\JsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\JsonApi\Object\ResourceIdentifier;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

/**
 * Class InboundRequest
 *
 * @package CloudCreativity\JsonApi
 */
class InboundRequest implements RequestInterface
{

    /**
     * The HTTP method.
     *
     * @var string
     */
    private $method;

    /**
     * The requested resource type.
     *
     * @var string
     */
    private $resourceType;

    /**
     * The requested resource id.
     *
     * @var string|null
     */
    private $resourceId;

    /**
     * The requested resource relationship.
     *
     * @var string|null
     */
    private $relationshipName;

    /**
     * Whether the keyword 'relationships' is in the URL.
     *
     * @var bool
     */
    private $relationships;

    /**
     * The inbound JSON API document.
     *
     * @var DocumentInterface|null
     */
    private $document;

    /**
     * The inbound encoding parameters.
     *
     * @var EncodingParametersInterface
     */
    private $parameters;

    /**
     * InboundRequest constructor.
     *
     * @param $method
     * @param $resourceType
     * @param string|null $resourceId
     * @param string|null $relationshipName
     * @param bool $relationships
     * @param DocumentInterface|null $document
     * @param EncodingParametersInterface|null $parameters
     */
    public function __construct(
        $method,
        $resourceType,
        $resourceId = null,
        $relationshipName = null,
        $relationships = false,
        DocumentInterface $document = null,
        EncodingParametersInterface $parameters = null
    ) {
        $this->method = strtoupper($method);
        $this->resourceType = $resourceType;
        $this->resourceId = $resourceId;
        $this->relationshipName = $relationshipName;
        $this->relationships = (bool) $relationships;
        $this->document = $document;
        $this->parameters = $parameters ?: new EncodingParameters();
    }

    /**
     * @inheritdoc
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * @inheritdoc
     */
    public function getResourceId()
    {
        return $this->resourceId;
    }

    /**
     * @inheritdoc
     */
    public function getResourceIdentifier()
    {
        if (!$this->resourceId) {
            return null;
        }

        return ResourceIdentifier::create($this->resourceType, $this->resourceId);
    }

    /**
     * @inheritdoc
     */
    public function getRelationshipName()
    {
        return $this->relationshipName;
    }

    /**
     * @inheritdoc
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @inheritdoc
     */
    public function getDocument()
    {
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
        return $this->relationships;
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
    protected function isResource()
    {
        return !empty($this->getResourceId());
    }

    /**
     * @return bool
     */
    protected function isRelationship()
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
    protected function isMethod($method)
    {
        return $this->method === strtoupper($method);
    }

}
