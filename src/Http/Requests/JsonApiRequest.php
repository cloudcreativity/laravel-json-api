<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

use CloudCreativity\JsonApi\Contracts\Authorizer\AuthorizerInterface;
use CloudCreativity\JsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\JsonApi\Object\Document;
use CloudCreativity\JsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Exceptions\RequestException;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request as HttpRequest;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class Request
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiRequest implements ValidatesWhenResolved
{

    use InterpretsHttpRequests,
        DecodesDocuments,
        ParsesQueryParameters;

    /**
     * The resource type that this request relates to.
     *
     * @var string
     */
    protected $resourceType;

    /**
     * A list of has-one relationships that are expected as endpoints.
     *
     * @var array
     */
    protected $hasOne = [];

    /**
     * A list of has-many relationships that are exposed as endpoints.
     *
     * @var array
     */
    protected $hasMany = [];

    /**
     * @var string[]|null
     * @see ParsesQueryParameters::allowedIncludePaths()
     */
    protected $allowedIncludePaths = [];

    /**
     * @var array|null
     * @see ParsesQueryParameters::allowedFieldSetTypes()
     */
    protected $allowedFieldSetTypes = null;

    /**
     * @var string[]|null
     * @see ParsesQueryParameters::allowedSortParameters()
     */
    protected $allowedSortParameters = [];

    /**
     * @var string[]|null
     * @see ParsesQueryParameters::allowedPagingParameters()
     */
    protected $allowedPagingParameters = [];

    /**
     * @var string[]|null
     * @see ParsesQueryParameters::allowedFilteringParameters()
     */
    protected $allowedFilteringParameters = [];

    /**
     * @var bool
     * @see ParsesQueryParameters::allowUnrecognizedParameters()
     */
    protected $allowUnrecognizedParams = false;

    /**
     * @var ValidatorProviderInterface|null
     */
    private $validators;

    /**
     * @var AuthorizerInterface|null
     */
    private $authorizer;

    /**
     * @var HttpRequest
     */
    private $request;

    /**
     * @var EncodingParametersInterface
     */
    private $encodingParameters;

    /**
     * @var DocumentInterface
     */
    private $document;

    /**
     * @var object|null
     */
    private $record;

    /**
     * AbstractRequest constructor.
     * @param HttpRequest $request
     * @param ValidatorProviderInterface $validators
     * @param AuthorizerInterface|null $authorizer
     */
    public function __construct(
        HttpRequest $request,
        ValidatorProviderInterface $validators = null,
        AuthorizerInterface $authorizer = null
    ) {
        $this->request = $request;
        $this->validators = $validators;
        $this->authorizer = $authorizer;
    }

    /**
     * Validate the given class instance.
     *
     * @return void
     * @throws JsonApiException
     * @throws HttpException
     */
    public function validate()
    {
        $this->record = !empty($this->resourceId()) ? $this->findRecord() : null;
        $this->validateRelationshipUrl();

        $this->encodingParameters = $this->validateParameters();

        if (!$this->authorizeBeforeValidation()) {
            throw new JsonApiException($this->authorizer->error());
        }

        if ($this->isExpectingDocument()) {
            $this->document = $this->decodeDocument($this->request);
            $this->validateDocument();
        }

        if (!$this->authorizeAfterValidation()) {
            throw new JsonApiException($this->authorizer->error());
        }
    }

    /**
     * @return object
     */
    public function record()
    {
        if (!is_object($this->record)) {
            throw new RequestException('This request does not relate to a record.');
        }

        return $this->record;
    }

    /**
     * @return DocumentInterface
     */
    public function document()
    {
        return $this->document ?: new Document();
    }

    /**
     * @return EncodingParametersInterface
     */
    public function parameters()
    {
        return $this->encodingParameters;
    }

    /**
     * Get the HTTP request object.
     *
     * @return HttpRequest
     */
    public function request()
    {
        return $this->request;
    }

    /**
     * @return bool
     */
    protected function authorizeBeforeValidation()
    {
        if (!$this->authorizer) {
            return true;
        }

        $parameters = $this->parameters();

        /** Index */
        if ($this->isIndex()) {
            return $this->authorizer->canReadMany($parameters);
        } /** Read Resource */
        elseif ($this->isReadResource()) {
            return $this->authorizer->canRead($this->record(), $parameters);
        } /** Update Resource */
        elseif ($this->isUpdateResource()) {
            return $this->authorizer->canUpdate($this->record(), $parameters);
        } /** Delete Resource */
        elseif ($this->isDeleteResource()) {
            return $this->authorizer->canDelete($this->record(), $parameters);
        } elseif ($this->isReadRelatedResource()) {
            return $this->authorizer->canReadRelatedResource($this->relationshipName(), $this->record(), $parameters);
        } /** Read Relationship Data */
        elseif ($this->isReadRelationship()) {
            return $this->authorizer->canReadRelationship($this->relationshipName(), $this->record(), $parameters);
        } /** Replace Relationship Data */
        elseif ($this->isReplaceRelationship()) {
            return $this->authorizer->canReplaceRelationship($this->relationshipName(), $this->record(), $parameters);
        } /** Add To Relationship Data */
        elseif ($this->isAddToRelationship()) {
            return $this->authorizer->canAddToRelationship($this->relationshipName(), $this->record(), $parameters);
        } /** Remove from Relationship Data */
        elseif ($this->isRemoveFromRelationship()) {
            return $this->authorizer->canRemoveFromRelationship($this->relationshipName(), $this->record(), $parameters);
        }

        return true;
    }

    /**
     * Is the request authorized?
     *
     * @return bool
     */
    protected function authorizeAfterValidation()
    {
        if ($this->authorizer && $this->isCreateResource()) {
            return $this->authorizer->canCreate($this->document()->resource(), $this->parameters());
        }

        return true;
    }

    /**
     * @return object
     * @throws NotFoundHttpException
     */
    protected function findRecord()
    {
        /** @var StoreInterface $store */
        $store = app(StoreInterface::class);
        $identifier = ResourceIdentifier::create($this->resourceType, $this->resourceId());

        $record = $store->find($identifier);

        if (!$record) {
            throw new NotFoundHttpException();
        }

        return $record;
    }

    /**
     * @return void
     * @throws NotFoundHttpException
     */
    protected function validateRelationshipUrl()
    {
        if (!$this->isRelationship()) {
            return;
        }

        $name = $this->relationshipName();

        if (!in_array($name, $this->hasOne) && !in_array($name, $this->hasMany)) {
            throw new NotFoundHttpException();
        }
    }

    /**
     * @return EncodingParametersInterface
     */
    protected function validateParameters()
    {
        $parameters = $this->parseQueryParameters();
        $this->checkQueryParameters($parameters);

        return $parameters;
    }

    /**
     * @return DocumentValidatorInterface|null
     */
    protected function validator()
    {
        if (!$this->validators) {
            return null;
        }

        /** Create Resource */
        if ($this->isCreateResource()) {
            return $this->validators->createResource();
        } /** Update Resource */
        elseif ($this->isUpdateResource()) {
            return $this->validators->updateResource($this->record(), $this->resourceId());
        } /** Replace Relationship */
        elseif ($this->isReplaceRelationship()) {
            return $this->validators->replaceRelationship($this->relationshipName(), $this->record());
        } /** Add To Relationship */
        elseif ($this->isAddToRelationship()) {
            return $this->validators->addToRelationship($this->relationshipName(), $this->record());
        } /** Remove From Relationship */
        elseif ($this->isRemoveFromRelationship()) {
            return $this->validators->removeFromRelationship($this->relationshipName(), $this->record());
        }

        return null;
    }

    /**
     * @return void
     * @throws JsonApiException
     */
    protected function validateDocument()
    {
        $validator = $this->validator();

        if ($validator && !$validator->isValid($this->document())) {
            throw new JsonApiException($validator->errors());
        }
    }

    /**
     * @return bool
     */
    protected function allowUnrecognizedParameters()
    {
        return $this->allowUnrecognizedParams;
    }

    /**
     * @return string[]|null
     */
    protected function allowedIncludePaths()
    {
        return $this->allowedIncludePaths;
    }

    /**
     * @return array|null
     */
    protected function allowedFieldSetTypes()
    {
        return $this->allowedFieldSetTypes;
    }

    /**
     * @return string[]|null
     */
    protected function allowedSortParameters()
    {
        return $this->allowedSortParameters;
    }

    /**
     * @return string[]|null
     */
    protected function allowedPagingParameters()
    {
        return $this->allowedPagingParameters;
    }

    /**
     * @return string[]|null
     */
    protected function allowedFilteringParameters()
    {
        return $this->allowedFilteringParameters;
    }

}
