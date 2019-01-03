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

namespace CloudCreativity\LaravelJsonApi\Validators;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\AttributesValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\QueryValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ResourceValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Http\Query\ChecksQueryParameters;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Str;
use Neomerx\JsonApi\Contracts\Http\Query\QueryCheckerInterface;

/**
 * Class AbstractValidatorProvider
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0 extend `Validation\AbstractValidators` instead.
 */
abstract class AbstractValidatorProvider implements ValidatorProviderInterface
{

    use ChecksQueryParameters;

    /**
     * The resource type the validators relate to.
     *
     * @var string
     */
    protected $resourceType;

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
     * Validation rules for query parameters.
     *
     * @var array
     */
    protected $queryRules = [];

    /**
     * Custom messages for the query parameters validator.
     *
     * @var array
     */
    protected $queryMessages = [];

    /**
     * Custom attributes for the query parameters validator.
     *
     * @var array
     */
    protected $queryCustomAttributes = [];

    /**
     * The allowed filtering parameters.
     *
     * By default we set this to `null` to allow any filtering parameters, as we expect
     * the filtering parameters to be validated using the query parameter validator.
     *
     * @var string[]|null
     * @see ChecksQueryParameters::allowedFilteringParameters()
     */
    protected $allowedFilteringParameters = null;

    /**
     * The allowed paging parameters.
     *
     * By default we set this to `null` to allow any paging parameters, as we expect
     * the paging parameters to be validated using the query parameter validator.
     *
     * @var string[]|null
     * @see ChecksQueryParameters::allowedPagingParameters()
     */
    protected $allowedPagingParameters = null;

    /**
     * @var Api
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
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     * @return array
     */
    abstract protected function attributeRules($record = null);

    /**
     * Define the validation rules for the resource relationships.
     *
     * @param RelationshipsValidatorInterface $relationships
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     * @return void
     */
    abstract protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null);

    /**
     * AbstractValidatorProvider constructor.
     *
     * @param Api $api
     * @param FactoryInterface $factory
     */
    public function __construct(Api $api, FactoryInterface $factory)
    {
        $this->api = $api;
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     */
    public function createResource()
    {
        $validator = $this->resourceValidator();

        return $this->validatorFactory()->resourceDocument($validator);
    }

    /**
     * @inheritdoc
     */
    public function updateResource($resourceId, $record)
    {
        $validator = $this->resourceValidator($resourceId, $record);

        return $this->validatorFactory()->resourceDocument($validator);
    }

    /**
     * @inheritdoc
     */
    public function modifyRelationship($resourceId, $relationshipName, $record)
    {
        $validator = $this
            ->resourceRelationships($record)
            ->get($relationshipName);

        return $this->validatorFactory()->relationshipDocument($validator);
    }

    /**
     * @return QueryCheckerInterface
     * @deprecated use `searchQueryChecker` instead.
     */
    public function queryChecker()
    {
        return $this->createQueryChecker($this->factory, $this->queryValidator());
    }

    /**
     * @inheritDoc
     */
    public function resourceQueryChecker()
    {
        /**
         * Allow filter params, but not sort and page, for a resource GET.
         *
         * @see https://github.com/cloudcreativity/laravel-json-api/issues/218
         */
        if (request()->isMethod('GET')) {
            return $this->factory->createExtendedQueryChecker(
                $this->allowUnrecognizedParameters(),
                $this->allowedIncludePaths(),
                $this->allowedFieldSetTypes(),
                [],
                [],
                $this->allowedFilteringParametersWithoutId(),
                $this->queryValidatorWithoutSortAndPage()
            );
        }

        /**
         * For modify resource requests, do not allow filter, sort and page.
         */
        return $this->factory->createExtendedQueryChecker(
            $this->allowUnrecognizedParameters(),
            $this->allowedIncludePaths(),
            $this->allowedFieldSetTypes(),
            [],
            [],
            [],
            $this->queryValidatorWithoutSearch()
        );
    }

    /**
     * @inheritDoc
     */
    public function searchQueryChecker()
    {
        return $this->queryChecker();
    }

    /**
     * @inheritDoc
     */
    public function relatedQueryChecker()
    {
        return $this->queryChecker();
    }

    /**
     * @inheritDoc
     */
    public function relationshipQueryChecker()
    {
        return $this->queryChecker();
    }


    /**
     * @return string
     */
    protected function getResourceType()
    {
        if (!$this->resourceType) {
            throw new RuntimeException('Expecting a resource type to be set.');
        }

        return $this->resourceType;
    }

    /**
     * Callback to configure an attributes validator.
     *
     * Child classes can override this method if they need to do custom configuration
     * on the attributes validator.
     *
     * @param Validator $validator
     *      the Laravel validator instance that will validate the attributes.
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     */
    protected function conditionalAttributes(Validator $validator, $record = null)
    {
        // no-op
    }

    /**
     * Extract attributes for validation from the supplied resource.
     *
     * Child classes can override this method if they need to customise the extraction
     * of attributes from the supplied resource. Returning null from this function means
     * the validator will extract the attributes itself. If you are customising the
     * extraction of attributes, you MUST return an array from this method.
     *
     * @param ResourceObjectInterface $resource
     * @param object|null $record
     * @return array|null
     */
    protected function extractAttributes(ResourceObjectInterface $resource, $record = null)
    {
        return null;
    }

    /**
     * @param string|null $resourceId
     * @param object|null $record
     * @return ResourceValidatorInterface
     */
    protected function resourceValidator($resourceId = null, $record = null)
    {
        return $this->validatorFactory()->resource(
            $this->getResourceType(),
            $resourceId,
            $this->resourceAttributes($record),
            $this->resourceRelationships($record),
            $this->resourceContext($record)
        );
    }

    /**
     * Get a validator for the resource attributes member.
     *
     * @param object|null $record
     * @return AttributesValidatorInterface
     */
    protected function resourceAttributes($record = null)
    {
        return $this->validatorFactory()->attributes(
            $this->attributeRules($record),
            $this->attributeMessages($record),
            $this->attributeCustomAttributes($record),
            function (Validator $validator) use ($record) {
                return $this->conditionalAttributes($validator, $record);
            },
            function (ResourceObjectInterface $resource, $record) {
                return $this->extractAttributes($resource, $record);
            }
        );
    }

    /**
     * @param object|null $record
     * @return array
     */
    protected function attributeMessages($record = null)
    {
        return $this->messages;
    }

    /**
     * @param object|null $record
     * @return array
     */
    protected function attributeCustomAttributes($record = null)
    {
        return $this->customAttributes;
    }

    /**
     * Get a validator for the resource relationships member.
     *.
     *
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     * @return RelationshipsValidatorInterface
     */
    protected function resourceRelationships($record = null)
    {
        $validator = $this->validatorFactory()->relationships();
        $this->relationshipRules($validator, $record);

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
     * @param object|null $record
     * @return ResourceValidatorInterface|null
     */
    protected function resourceContext($record = null)
    {
        return null;
    }

    /**
     * Get a validator for all query parameters.
     *
     * @return QueryValidatorInterface
     */
    protected function queryValidator()
    {
        return $this->validatorFactory()->queryParameters(
            $this->queryRules(),
            $this->queryMessages(),
            $this->queryCustomAttributes(),
            function (Validator $validator) {
                return $this->conditionalQuery($validator);
            }
        );
    }

    /**
     * Get a validator for query parameters except for filter, sort and page.
     *
     * @return QueryValidatorInterface
     */
    protected function queryValidatorWithoutSearch()
    {
        return $this->validatorFactory()->queryParameters(
            $this->queryRulesWithoutSearch(),
            $this->queryMessages(),
            $this->queryCustomAttributes(),
            function (Validator $validator) {
                return $this->conditionalQuery($validator);
            }
        );
    }

    /**
     * @return QueryValidatorInterface
     */
    protected function queryValidatorWithoutSortAndPage()
    {
        return $this->validatorFactory()->queryParameters(
            $this->queryRulesWithoutSortAndPage(),
            $this->queryMessages(),
            $this->queryCustomAttributes(),
            function (Validator $validator) {
                return $this->conditionalQuery($validator);
            }
        );
    }

    /**
     * Get the validation rules for the query parameters.
     *
     * @return array
     */
    protected function queryRules()
    {
        return $this->queryRules;
    }

    /**
     * Get the validation rules for query parameters, excluding filter, sort and page.
     *
     * @return array
     */
    protected function queryRulesWithoutSearch()
    {
        return collect($this->queryRules())->reject(function ($value, $key) {
            return Str::startsWith($key, ['filter.', 'sort.', 'page.']);
        })->all();
    }

    /**
     * Get the validation rules for query parameters excluding sort and page.
     *
     * @return array
     */
    protected function queryRulesWithoutSortAndPage()
    {
        return collect($this->queryRules())->reject(function ($value, $key) {
            return Str::startsWith($key, ['sort.', 'page.']);
        })->all();
    }

    /**
     * @return array
     */
    protected function allowedFilteringParametersWithoutId()
    {
        return collect($this->allowedFilteringParameters())->reject('id')->values()->all();
    }

    /**
     * @return array
     */
    protected function queryMessages()
    {
        return $this->queryMessages;
    }

    /**
     * @return array
     */
    protected function queryCustomAttributes()
    {
        return $this->queryCustomAttributes;
    }

    /**
     * Callback to configure a query parameter validator.
     *
     * Child classes can override this method if they need to do custom
     * configuration on the query parameter validator.
     *
     * @param Validator $validator
     *      the Laravel validator instance that will validate the query parameters.
     */
    protected function conditionalQuery(Validator $validator)
    {
        // no-op
    }

    /**
     * @return ValidatorFactoryInterface
     */
    protected function validatorFactory()
    {
        if (!$this->validatorFactory) {
            $this->validatorFactory = $this->api->validators();
        }

        return $this->validatorFactory;
    }
}
