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

use CloudCreativity\LaravelJsonApi\Auth\HandlesAuthorizers;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Exceptions\ValidationException;
use CloudCreativity\LaravelJsonApi\Object\Document;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryCheckerInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class ValidatedRequest implements ValidatesWhenResolved
{

    use HandlesAuthorizers;

    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var StoreInterface
     */
    private $store;

    /**
     * ServerRequest constructor.
     *
     * @param RequestInterface $request
     * @param StoreInterface $store
     * @param ContainerInterface $container
     */
    public function __construct(RequestInterface $request, StoreInterface $store, ContainerInterface $container)
    {
        $this->request = $request;
        $this->store = $store;
        $this->container = $container;
    }

    /**
     * Get an item from the JSON API document using "dot" notation.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get($key, $default = null)
    {
        return data_get($this->request->getDocument(), $key, $default);
    }

    /**
     * Get the JSON API document as an array.
     *
     * @return array
     */
    public function all()
    {
        $document = $this->getDocument();

        return $document ? $document->toArray() : [];
    }

    /**
     * Get the resource type that the request is for.
     *
     * @return string|null
     */
    public function getResourceType()
    {
        return $this->request->getResourceType();
    }

    /**
     * Get the resource id that the request is for.
     *
     * @return string|null
     */
    public function getResourceId()
    {
        return $this->request->getResourceId();
    }

    /**
     * Get the relationship name that the request is for.
     *
     * @return string|null
     */
    public function getRelationshipName()
    {
        return $this->request->getRelationshipName();
    }

    /**
     * Get the record that the request relates to.
     *
     * @return object|null
     */
    public function getRecord()
    {
        return $this->request->getResource();
    }

    /**
     * Get the validated JSON API document, if there is one.
     *
     * @return DocumentInterface|null
     */
    public function getDocument()
    {
        if (!$document = $this->request->getDocument()) {
            return null;
        }

        return new Document($document);
    }

    /**
     * Get the validated JSON API encoding parameters.
     *
     * @return EncodingParametersInterface
     */
    public function getParameters()
    {
        return $this->request->getParameters();
    }

    /**
     * Validate the JSON API request.
     *
     * This method maintains compatibility with Laravel 5.4 and 5.5, as the `ValidatesWhenResolved`
     * method was renamed to `validateResolved` in 5.6.
     *
     * @return void
     */
    public function validate()
    {
        $this->validateResolved();
    }

    /**
     * @inheritdoc
     */
    public function validateResolved()
    {
        $this->authorize();

        $inverse = $this->request->getInverseResourceType();

        $resourceValidators = $this->container->getValidatorsByResourceType(
            $this->request->getResourceType()
        );

        if ($resourceValidators) {
            $this->validateRequest(
                $resourceValidators,
                $inverse ? $this->container->getValidatorsByResourceType($inverse) : null
            );
        }
    }

    /**
     * Authorize the request.
     *
     * @return void
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorize()
    {
        $authorizer = $this->container->getAuthorizerByResourceType($this->getResourceType());

        if ($authorizer) {
            $this->authorizeRequest($authorizer, $this->request, request());
        }
    }

    /**
     * Validate the inbound request query parameters and JSON API document.
     *
     * JSON API query parameters are checked using the primary resource's validators
     * if it is not a related resource request, or against the related resource's
     * validators if it is a relationship request. This is because the query parameters
     * for a relationship request actually relate to the related resource that will
     * be returned in the encoded response.
     *
     * So for a request to `GET /posts/1`, the `posts` validators are provided as
     * `$resource` and the query parameters are checked using this set of validators.
     * For a request to `GET /posts/1/comments` the query parameters are checked
     * against the `comments` validators, which are provided as `$related`.
     *
     * The JSON API document is always checked against the primary resource validators
     * (`$resource`) because the inbound document always relates to this primary
     * resource, even if modifying a relationship.
     *
     * @param ValidatorProviderInterface $resource
     *      validators for the primary resource.
     * @param ValidatorProviderInterface|null $related
     *      validators for the related resource, if the request is for a relationship.
     * @return void
     * @throws JsonApiException
     */
    protected function validateRequest(ValidatorProviderInterface $resource, ValidatorProviderInterface $related = null)
    {
        /** Check the JSON API query parameters */
        if (!$this->request->getRelationshipName()) {
            $this->checkQueryParameters($resource);
        } elseif ($related) {
            $this->checkQueryParameters($related);
        }

        /** Check the JSON API document is acceptable */
        $this->checkDocumentIsAcceptable($resource);
    }

    /**
     * @param ValidatorProviderInterface $validators
     * @throws JsonApiException
     */
    protected function checkQueryParameters(ValidatorProviderInterface $validators)
    {
        $checker = $this->queryChecker($validators);
        $checker->checkQuery($this->request->getParameters());
    }

    /**
     * @param ValidatorProviderInterface $validators
     * @throws JsonApiException
     */
    protected function checkDocumentIsAcceptable(ValidatorProviderInterface $validators)
    {
        $validator = $this->documentAcceptanceValidator($validators);
        $document = $this->getDocument();

        if ($validator && !$document && $this->isExpectingDocument()) {
            throw new DocumentRequiredException();
        }

        if ($validator && !$validator->isValid($document, $this->getRecord())) {
            throw new ValidationException($validator->getErrors());
        }
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
     * @return bool
     */
    protected function isExpectingDocument()
    {
        return $this->request->isCreateResource() ||
            $this->request->isUpdateResource() ||
            $this->request->isReplaceRelationship() ||
            $this->request->isAddToRelationship() ||
            $this->request->isRemoveFromRelationship();
    }

    /**
     * @param ValidatorProviderInterface $validators
     * @return DocumentValidatorInterface|null
     */
    protected function documentAcceptanceValidator(ValidatorProviderInterface $validators)
    {
        $resourceId = $this->request->getResourceId();
        $relationshipName = $this->request->getRelationshipName();

        /** Create Resource */
        if ($this->request->isCreateResource()) {
            return $validators->createResource();
        } /** Update Resource */
        elseif ($this->request->isUpdateResource()) {
            return $validators->updateResource($resourceId, $this->getRecord());
        } /** Replace Relationship */
        elseif ($this->request->isModifyRelationship()) {
            return $validators->modifyRelationship($resourceId, $relationshipName, $this->getRecord());
        }

        return null;
    }

    /**
     * @param ValidatorProviderInterface $validators
     * @return QueryCheckerInterface
     */
    protected function queryChecker(ValidatorProviderInterface $validators)
    {
        if ($this->request->isIndex()) {
            return $validators->searchQueryChecker();
        } elseif ($this->request->isReadRelatedResource()) {
            return $validators->relatedQueryChecker();
        } elseif ($this->request->hasRelationships()) {
            return $validators->relationshipQueryChecker();
        }

        return $validators->resourceQueryChecker();
    }

}
