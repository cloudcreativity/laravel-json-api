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

namespace CloudCreativity\LaravelJsonApi\Validators;

use CloudCreativity\JsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use CloudCreativity\JsonApi\Contracts\Http\HttpServiceInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceInterface;
use CloudCreativity\JsonApi\Contracts\Validators\AttributesValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\FilterValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ResourceValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\JsonApi\Validators\ChecksQueryParameters;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorFactoryInterface;
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
     * @param FactoryInterface $factory
     */
    public function __construct(ApiInterface $api, FactoryInterface $factory)
    {
        $this->api = $api;
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     */
    public function resource($resourceType, $resourceId = null)
    {
        $resource = $this->validatorFactory()->resource($resourceType, $resourceId);

        return $this->factory->resourceDocument($resource);
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
     * @todo need to use the filter validator as well.
     */
    public function queryChecker($resourceType)
    {
        return $this->createQueryChecker($this->factory);
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
     * Get a validator for the filter query parameters.
     *
     * @param string $resourceType
     *      the resource type that is being filtered
     * @return FilterValidatorInterface
     */
    protected function filterValidator($resourceType)
    {
        return $this->factory->filterParams(
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
