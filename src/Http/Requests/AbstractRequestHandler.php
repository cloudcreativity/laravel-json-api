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
use CloudCreativity\JsonApi\Contracts\Http\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Contracts\Validators\FilterValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestHandlerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PageParameterHandlerInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Factories\Factory;

/**
 * Class Request
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractRequestHandler implements RequestHandlerInterface
{

    use ChecksUrlParameters,
        ChecksQueryParameters,
        AuthorizesRequests,
        ChecksDocuments;

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
     * @see ParsesQueryParameters::allowedFilteringParameters()
     */
    protected $allowedFilteringParameters = [];

    /**
     * @var bool
     * @see ParsesQueryParameters::allowUnrecognizedParameters()
     */
    protected $allowUnrecognizedParams = false;

    /**
     * @var ValidatorProviderInterface
     */
    private $validators;

    /**
     * @var AuthorizerInterface|null
     */
    private $authorizer;

    /**
     * @var HttpFactoryInterface
     */
    private $factory;

    /**
     * AbstractRequest constructor.
     * @param AuthorizerInterface|null $authorizer
     * @param ValidatorProviderInterface|null $validators
     * @param HttpFactoryInterface|null $factory
     */
    public function __construct(
        AuthorizerInterface $authorizer = null,
        ValidatorProviderInterface $validators = null,
        HttpFactoryInterface $factory = null
    ) {
        $this->validators = $validators;
        $this->authorizer = $authorizer;
        $this->factory = $factory ?: new Factory();
    }

    /**
     * @inheritdoc
     */
    public function handle(RequestInterpreterInterface $interpreter, JsonApiRequest $request)
    {
        /** Check that a resource id has resolved to a record */
        $this->checkResourceId($interpreter, $request);

        /** Check the relationship is acceptable */
        if ($request->getRelationshipName()) {
            $this->checkRelationshipName($request);
        }

        /** Check request parameters are acceptable */
        $this->checkQueryParameters($this->factory, $request, $this->filterValidator());

        /** Authorize the request */
        if ($this->authorizer) {
            $this->authorize($interpreter, $this->authorizer, $request);
        }

        /** Check the document content is acceptable */
        if ($this->validators) {
            $this->checkDocumentIsAcceptable($this->validators, $interpreter, $request);
        }
    }

    /**
     * @inheritDoc
     */
    protected function allowedRelationships()
    {
        return array_merge($this->hasOne, $this->hasMany);
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
    protected function allowedFilteringParameters()
    {
        return $this->allowedFilteringParameters;
    }

    /**
     * @return string[]|null
     */
    protected function allowedPagingParameters()
    {
        /** @var PageParameterHandlerInterface $param */
        $param = app(PageParameterHandlerInterface::class);

        return $param->getAllowedPagingParameters();
    }

    /**
     * @return FilterValidatorInterface|null
     */
    private function filterValidator()
    {
        return $this->validators ? $this->validators->filterResources($this->getResourceType()) : null;
    }
}
