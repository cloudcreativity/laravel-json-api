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
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Http\Query\ChecksQueryParameters;
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
     * @inheritdoc
     */
    public function queryChecker()
    {
        return $this->createQueryChecker($this->factory, $this->queryValidator());
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
     * @param ResourceInterface $resource
     * @param object|null $record
     * @return array|null
     */
    protected function extractAttributes(ResourceInterface $resource, $record = null)
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
            function (ResourceInterface $resource, $record) {
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
     * Get a validator for the filter query parameters.
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
     * Get the validation rules for the query parameters.
     *
     * @return array
     */
    protected function queryRules()
    {
        return $this->queryRules;
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
            $this->validatorFactory = $this->factory->createValidatorFactory(
                $this->api->getErrors(),
                $this->api->getStore()
            );
        }

        return $this->validatorFactory;
    }
}
