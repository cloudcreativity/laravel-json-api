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

use CloudCreativity\LaravelJsonApi\Contracts\Auth\AuthorizerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\LaravelJsonApi\Encoder\Parameters\EncodingParameters;
use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Exceptions\ValidationException;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Object\Document;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Contracts\Validation\ValidatesWhenResolved;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryCheckerInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;

abstract class ValidatedRequest implements ValidatesWhenResolved
{

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RequestInterface
     */
    private $jsonApiRequest;

    /**
     * Authorize the request.
     *
     * @return void
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    abstract protected function authorize();

    /**
     * Validate the query parameters.
     *
     * @return void
     * @throws JsonApiException
     */
    abstract protected function validateQuery();

    /**
     * ValidatedRequest constructor.
     *
     * @param Request $httpRequest
     * @param ContainerInterface $container
     * @param Factory $factory
     * @param RequestInterface $jsonApiRequest
     */
    public function __construct(
        Request $httpRequest,
        ContainerInterface $container,
        Factory $factory,
        RequestInterface $jsonApiRequest
    ) {
        $this->request = $httpRequest;
        $this->factory = $factory;
        $this->container = $container;
        $this->jsonApiRequest = $jsonApiRequest;
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
        return $this->request->json($key, $default);
    }

    /**
     * Get the JSON API document as an array.
     *
     * @return array
     */
    public function all()
    {
        return $this->request->json()->all();
    }

    /**
     * Get the JSON API document as an object.
     *
     * @return object|null
     */
    public function decode()
    {
        return $this->jsonApiRequest->getDocument();
    }

    /**
     * Get the resource type that the request is for.
     *
     * @return string|null
     */
    public function getResourceType()
    {
        return $this->jsonApiRequest->getResourceType();
    }

    /**
     * Get the resource id that the request is for.
     *
     * @return string|null
     */
    public function getResourceId()
    {
        return $this->jsonApiRequest->getResourceId();
    }

    /**
     * Get the relationship name that the request is for.
     *
     * @return string|null
     */
    public function getRelationshipName()
    {
        return $this->jsonApiRequest->getRelationshipName();
    }

    /**
     * Get the record that the request relates to.
     *
     * @return object|null
     */
    public function getRecord()
    {
        return $this->jsonApiRequest->getResource();
    }

    /**
     * Get the validated JSON API document, if there is one.
     *
     * @return DocumentInterface|null
     * @deprecated
     */
    public function getDocument()
    {
        if (!$document = $this->jsonApiRequest->getDocument()) {
            return null;
        }

        return new Document($document);
    }

    /**
     * Get parsed query parameters.
     *
     * @return array
     */
    public function getQueryParameters()
    {
        return EncodingParameters::cast($this->getEncodingParameters())->toArray();
    }

    /**
     * Get the JSON API encoding parameters.
     *
     * @return EncodingParametersInterface
     * @deprecated 2.0.0 use `getEncodingParameters`
     */
    public function getParameters()
    {
        return $this->getEncodingParameters();
    }

    /**
     * @return EncodingParametersInterface
     */
    public function getEncodingParameters()
    {
        return $this->jsonApiRequest->getParameters();
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
        $this->validateQuery();
        $this->validateDocument();
    }

    /**
     * Validate the JSON API document.
     *
     * @return void
     * @throws JsonApiException
     */
    protected function validateDocument()
    {
        // no-op
    }


    /**
     * @return AuthorizerInterface|null
     */
    protected function getAuthorizer()
    {
        return $this->container->getAuthorizerByResourceType($this->getResourceType());
    }

    /**
     * Get the resource validators.
     *
     * @return ValidatorFactoryInterface|ValidatorProviderInterface|null
     */
    protected function getValidators()
    {
        return $this->container->getValidatorsByResourceType($this->getResourceType());
    }

    /**
     * Get the inverse resource validators.
     *
     * @return ValidatorFactoryInterface|ValidatorProviderInterface|null
     */
    protected function getInverseValidators()
    {
        return $this->container->getValidatorsByResourceType(
            $this->jsonApiRequest->getInverseResourceType()
        );
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
    private function validateRequest(ValidatorProviderInterface $resource, ValidatorProviderInterface $related = null)
    {
        /** Check the JSON API query parameters */
        if (!$this->jsonApiRequest->getRelationshipName()) {
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
    private function checkQueryParameters(ValidatorProviderInterface $validators)
    {
        $checker = $this->queryChecker($validators);
        $checker->checkQuery($this->jsonApiRequest->getParameters());
    }

    /**
     * @param ValidatorProviderInterface $validators
     * @throws JsonApiException
     */
    private function checkDocumentIsAcceptable(ValidatorProviderInterface $validators)
    {
        if (!$validator = $this->documentAcceptanceValidator($validators)) {
            return;
        }

        $document = $this->getDocument();

        if (!$document) {
            throw new DocumentRequiredException();
        }

        if (!$validator->isValid($document, $this->getRecord())) {
            throw new ValidationException($validator->getErrors());
        }
    }

    /**
     * @param ValidatorProviderInterface $validators
     * @return DocumentValidatorInterface|null
     */
    private function documentAcceptanceValidator(ValidatorProviderInterface $validators)
    {
        $resourceId = $this->jsonApiRequest->getResourceId();
        $relationshipName = $this->jsonApiRequest->getRelationshipName();

        /** Create Resource */
        if ($this->jsonApiRequest->isCreateResource()) {
            return $validators->createResource();
        } /** Update Resource */
        elseif ($this->jsonApiRequest->isUpdateResource()) {
            return $validators->updateResource($resourceId, $this->getRecord());
        } /** Replace Relationship */
        elseif ($this->jsonApiRequest->isModifyRelationship()) {
            return $validators->modifyRelationship($resourceId, $relationshipName, $this->getRecord());
        }

        return null;
    }

    /**
     * @param ValidatorProviderInterface $validators
     * @return QueryCheckerInterface
     */
    private function queryChecker(ValidatorProviderInterface $validators)
    {
        if ($this->jsonApiRequest->isIndex()) {
            return $validators->searchQueryChecker();
        } elseif ($this->jsonApiRequest->isReadRelatedResource()) {
            return $validators->relatedQueryChecker();
        } elseif ($this->jsonApiRequest->hasRelationships()) {
            return $validators->relationshipQueryChecker();
        }

        return $validators->resourceQueryChecker();
    }

}
