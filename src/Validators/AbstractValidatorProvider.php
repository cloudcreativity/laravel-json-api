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

namespace CloudCreativity\LaravelJsonApi\Validators;

use CloudCreativity\JsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceInterface;
use CloudCreativity\JsonApi\Contracts\Validators\AttributesValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\QueryValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ResourceValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\JsonApi\Http\Query\ChecksQueryParameters;
use CloudCreativity\JsonApi\Validators\QueryValidatorIterator;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Http\Requests\RequestInterpreter;
use Illuminate\Contracts\Validation\Validator;

/**
 * Class AbstractValidatorProvider
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractValidatorProvider implements ValidatorProviderInterface
{

    use ChecksQueryParameters;

    /**
     * Custom messages for the attributes validator.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Custom attributes for the attributes validator.
     *
     * @var array
     */
    protected $customAttributes = [];

    /**
     * Validation rules for filter query parameters.
     *
     * @var array
     */
    protected $filterRules = [];

    /**
     * Custom messages for the filter validator.
     *
     * @var array
     */
    protected $filterMessages = [];

    /**
     * Custom attributes for the filter validator.
     *
     * @var array
     */
    protected $filterCustomAttributes = [];

    /**
     * Validation rules for pagination query parameters.
     *
     * @var array
     */
    protected $paginationRules = [];

    /**
     * Custom messages for the pagination validator.
     *
     * @var array
     */
    protected $paginationMessages = [];

    /**
     * Custom attributes for the pagination validator.
     *
     * @var array
     */
    protected $paginationCustomAttributes = [];

    /**
     * The allowed filtering parameters.
     *
     * By default we set this to `null` to allow any filtering parameters, as we expect
     * the filtering parameters to be validated using the filter validator.
     *
     * @var string[]|null
     * @see ChecksQueryParameters::allowedFilteringParameters()
     */
    protected $allowedFilteringParameters = null;

    /**
     * The allowed paging parameters.
     *
     * By default we set this to `null` to allow any paging parameters, as we expect
     * the paging parameters to be validated using the pagination validator.
     *
     * @var string[]|null
     * @see ChecksQueryParameters::allowedPagingParameters()
     */
    protected $allowedPagingParameters = null;

    /**
     * @var RequestInterpreter
     */
    protected $requestInterpreter;

    /**
     * @var ApiInterface
     */
    private $api;

    /**
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var ValidatorFactoryInterface
     */
    private $validatorFactory;

    /**
     * Get the validation rules for the resource attributes.
     *
     * @param string $resourceType
     *      the resource type being validated
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     * @return array
     */
    abstract protected function attributeRules($resourceType, $record = null);

    /**
     * Define the validation rules for the resource relationships.
     *
     * @param RelationshipsValidatorInterface $relationships
     * @param string $resourceType
     *      the resource type being validated
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     * @return void
     */
    abstract protected function relationshipRules(
        RelationshipsValidatorInterface $relationships,
        $resourceType,
        $record = null
    );

    /**
     * AbstractValidatorProvider constructor.
     *
     * @param ApiInterface $api
     * @param RequestInterpreter $interpreter
     * @param FactoryInterface $factory
     */
    public function __construct(ApiInterface $api, RequestInterpreter $interpreter, FactoryInterface $factory)
    {
        $this->api = $api;
        $this->requestInterpreter = $interpreter;
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     */
    public function createResource($resourceType)
    {
        $validator = $this->resourceValidator($resourceType);

        return $this->validatorFactory()->resourceDocument($validator);
    }

    /**
     * @inheritdoc
     */
    public function updateResource($resourceType, $resourceId, $record)
    {
        $validator = $this->resourceValidator($resourceType, $resourceId, $record);

        return $this->validatorFactory()->resourceDocument($validator);
    }

    /**
     * @inheritdoc
     */
    public function modifyRelationship($resourceType, $resourceId, $relationshipName, $record)
    {
        $validator = $this
            ->resourceRelationships($resourceType, $record)
            ->get($relationshipName);

        return $this->validatorFactory()->relationshipDocument($validator);
    }

    /**
     * @inheritdoc
     */
    public function queryChecker($resourceType)
    {
        return $this->createQueryChecker($this->factory, $this->createQueryValidator($resourceType));
    }

    /**
     * Callback to configure an attributes validator.
     *
     * Child classes can override this method if they need to do custom configuration
     * on the attributes validator.
     *
     * @param Validator $validator
     *      the Laravel validator instance that will validate the attributes.
     * @param string $resourceType
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     */
    protected function conditionalAttributes(Validator $validator, $resourceType, $record = null)
    {

    }

    /**
     * Extract attributes for validation from the supplied resource.
     *
     * Child classes can override this method if they need to customise the extraction
     * of attributes from the supplied resource. Returning null from this function means
     * the validator will extract the attributes itself. If you are customising the
     * extraction of attributes, you must return an array from this method.
     *
     * @param ResourceInterface $resource
     * @param string $resourceType
     * @param object|null $record
     * @return array|null
     */
    protected function extractAttributes(ResourceInterface $resource, $resourceType, $record = null)
    {

    }

    /**
     * @param string $resourceType
     * @param string|null $resourceId
     * @param object|null $record
     * @return ResourceValidatorInterface
     */
    protected function resourceValidator($resourceType, $resourceId = null, $record = null)
    {
        return $this->validatorFactory()->resource(
            $resourceType,
            $resourceId,
            $this->resourceAttributes($resourceType, $record),
            $this->resourceRelationships($resourceType, $record),
            $this->resourceContext($resourceType, $record)
        );
    }

    /**
     * Get a validator for the resource attributes member.
     *
     * @param string $resourceType
     *      the resource type being validated
     * @param object|null $record
     * @return AttributesValidatorInterface
     */
    protected function resourceAttributes($resourceType, $record = null)
    {
        return $this->validatorFactory()->attributes(
            $this->attributeRules($resourceType, $record),
            $this->attributeMessages($resourceType, $record),
            $this->attributeCustomAttributes($resourceType, $record),
            function (Validator $validator) use ($resourceType, $record) {
                return $this->conditionalAttributes($validator, $resourceType, $record);
            },
            function (ResourceInterface $resource, $record) use ($resourceType) {
                return $this->extractAttributes($resource, $resourceType, $record);
            }
        );
    }

    /**
     * @param $resourceType
     * @param object|null $record
     * @return array
     */
    protected function attributeMessages($resourceType, $record = null)
    {
        return $this->messages;
    }

    /**
     * @param $resourceType
     * @param object|null $record
     * @return array
     */
    protected function attributeCustomAttributes($resourceType, $record = null)
    {
        return $this->customAttributes;
    }

    /**
     * Get a validator for the resource relationships member.
     *
     * @param string $resourceType
     *      the resource type being validated.
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     * @return RelationshipsValidatorInterface
     */
    protected function resourceRelationships($resourceType, $record = null)
    {
        $validator = $this->validatorFactory()->relationships();
        $this->relationshipRules($validator, $resourceType, $record);

        return $validator;
    }

    /**
     * Get a context validator for the resource.
     *
     * The context validator validates the whole resource, once all its constituent
     * parts have passed validation - i.e. the type, id, attributes and relationships
     * will all be valid.
     *
     * Child classes can override this method to return their own validator if
     * needed.
     *
     * @param string $resourceType
     * @param object|null $record
     * @return ResourceValidatorInterface|null
     */
    protected function resourceContext($resourceType, $record = null)
    {
        return null;
    }

    /**
     * @param $resourceType
     * @return QueryValidatorInterface|null
     */
    protected function createQueryValidator($resourceType)
    {
        $validator = new QueryValidatorIterator();

        if ($this->requestInterpreter->isIndex()) {
            $validator->add($this->filterValidator($resourceType));
            $validator->add($this->paginationValidator($resourceType));
        }

        return $validator;
    }

    /**
     * Get a validator for the filter query parameters.
     *
     * @param string $resourceType
     *      the resource type that is being filtered
     * @return QueryValidatorInterface
     */
    protected function filterValidator($resourceType)
    {
        return $this->validatorFactory()->filterParams(
            $this->filterRules($resourceType),
            $this->filterMessages($resourceType),
            $this->filterCustomAttributes($resourceType),
            function (Validator $validator) use ($resourceType) {
                return $this->conditionalFilters($validator, $resourceType);
            }
        );
    }

    /**
     * Get the validation rules for the filter query parameter.
     *
     * @param $resourceType
     *      the resource type that is being filtered
     * @return array
     */
    protected function filterRules($resourceType)
    {
        return $this->filterRules;
    }

    /**
     * @param $resourceType
     *      the resource type that is being filtered
     * @return array
     */
    protected function filterMessages($resourceType)
    {
        return $this->filterMessages;
    }

    /**
     * @param $resourceType
     *      the resource type that is being filtered
     * @return array
     */
    protected function filterCustomAttributes($resourceType)
    {
        return $this->filterCustomAttributes;
    }

    /**
     * Callback to configure a filter validator.
     *
     * Child classes can override this method if they need to do custom
     * configuration on the filter validator.
     *
     * @param Validator $validator
     *      the Laravel validator instance that will validate the filters.
     * @param string $resourceType
     *      the resource type being filtered
     */
    protected function conditionalFilters(Validator $validator, $resourceType)
    {

    }

    /**
     * Get a validator for the pagination query parameters.
     *
     * @param string $resourceType
     *      the resource type that is being paged.
     * @return QueryValidatorInterface
     */
    protected function paginationValidator($resourceType)
    {
        return $this->validatorFactory()->paginationParams(
            $this->paginationRules($resourceType),
            $this->paginationMessages($resourceType),
            $this->paginationCustomAttributes($resourceType),
            function (Validator $validator) use ($resourceType) {
                return $this->conditionalPagination($validator, $resourceType);
            }
        );
    }

    /**
     * Get the validation rules for the pagination query parameter.
     *
     * @param $resourceType
     *      the resource type that is being paged
     * @return array
     */
    protected function paginationRules($resourceType)
    {
        return $this->paginationRules;
    }

    /**
     * @param $resourceType
     *      the resource type that is being paged
     * @return array
     */
    protected function paginationMessages($resourceType)
    {
        return $this->paginationMessages;
    }

    /**
     * @param $resourceType
     *      the resource type that is being paged
     * @return array
     */
    protected function paginationCustomAttributes($resourceType)
    {
        return $this->paginationCustomAttributes;
    }

    /**
     * Callback to configure a pagination validator.
     *
     * Child classes can override this method if they need to do custom
     * configuration on the pagination validator.
     *
     * @param Validator $validator
     *      the Laravel validator instance that will validate the pagination params.
     * @param string $resourceType
     *      the resource type being paged
     */
    protected function conditionalPagination(Validator $validator, $resourceType)
    {

    }

    /**
     * @return ValidatorFactoryInterface
     */
    protected function validatorFactory()
    {
        if (!$this->validatorFactory) {
            $this->validatorFactory = $this->factory->createValidatorFactory(
                $this->api->getErrors(),
                $this->api->getStore()
            );
        }

        return $this->validatorFactory;
    }
}
