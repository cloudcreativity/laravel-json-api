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

use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\AcceptRelatedResourceInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\AttributesValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\RelationshipValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ResourceValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorFactoryInterface;
use Illuminate\Contracts\Validation\Factory;

/**
 * Class ValidatorFactory
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0 use classes in the `Validation` namespace instead.
 */
class ValidatorFactory implements ValidatorFactoryInterface
{

    /**
     * @var Factory
     */
    private $validatorFactory;

    /**
     * @var ValidatorErrorFactoryInterface
     */
    protected $validationErrors;

    /**
     * @var StoreInterface
     */
    private $store;

    /**
     * ValidatorFactory constructor.
     *
     * @param ValidatorErrorFactoryInterface $validationErrors
     * @param StoreInterface $store
     * @param Factory $validatorFactory
     */
    public function __construct(
        ValidatorErrorFactoryInterface $validationErrors,
        StoreInterface $store,
        Factory $validatorFactory
    ) {
        $this->store = $store;
        $this->validationErrors = $validationErrors;
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * @inheritdoc
     */
    public function resourceDocument(ResourceValidatorInterface $resource = null)
    {
        return new ResourceDocumentValidator(
            $this->validationErrors,
            $resource ?: $this->resource()
        );
    }

    /**
     * @inheritdoc
     */
    public function relationshipDocument(RelationshipValidatorInterface $relationship = null)
    {
        return new RelationshipDocumentValidator(
            $this->validationErrors,
            $relationship ?: $this->relationship()
        );
    }

    /**
     * @inheritdoc
     */
    public function resource(
        $expectedType = null,
        $expectedId = null,
        AttributesValidatorInterface $attributes = null,
        RelationshipsValidatorInterface $relationships = null,
        ResourceValidatorInterface $context = null
    ) {
        return new ResourceValidator(
            $this->validationErrors,
            $expectedType,
            $expectedId,
            $attributes,
            $relationships ?: $this->relationships(),
            $context
        );
    }

    /**
     * @inheritdoc
     */
    public function relationships()
    {
        return new RelationshipsValidator($this->validationErrors, $this);
    }

    /**
     * @inheritDoc
     */
    public function relationship($expectedType = null, $allowEmpty = true, $acceptable = null)
    {
        return new RelationshipValidator(
            $this->validationErrors,
            $this->store,
            $expectedType,
            $allowEmpty,
            $acceptable
        );
    }

    /**
     * @inheritdoc
     */
    public function hasOne($expectedType, $allowEmpty = true, $acceptable = null)
    {
        return new HasOneValidator(
            $this->validationErrors,
            $this->store,
            $expectedType,
            $allowEmpty,
            $this->acceptableRelationship($acceptable)
        );
    }

    /**
     * @inheritdoc
     */
    public function hasMany($expectedType, $allowEmpty = false, $acceptable = null)
    {
        return new HasManyValidator(
            $this->validationErrors,
            $this->store,
            $expectedType,
            $allowEmpty,
            $this->acceptableRelationship($acceptable)
        );
    }

    /**
     * @param $acceptable
     * @return AcceptRelatedResourceInterface|null
     */
    protected function acceptableRelationship($acceptable)
    {
        if (!is_null($acceptable) && !$acceptable instanceof AcceptRelatedResourceInterface) {
            $acceptable = new AcceptRelatedResourceCallback($acceptable);
        }

        return $acceptable;
    }

    /**
     * @inheritdoc
     */
    public function attributes(
        array $rules,
        array $messages = [],
        array $customAttributes = [],
        callable $callback = null,
        callable $extractor = null
    ) {
        return new AttributesValidator(
            $this->validatorFactory,
            $this->validationErrors,
            $rules,
            $messages,
            $customAttributes,
            $callback,
            $extractor
        );
    }

    /**
     * @inheritdoc
     */
    public function queryParameters(
        array $rules,
        array $messages = [],
        array $customAttributes = [],
        callable $callback = null
    ) {
        return new QueryValidator(
            $this->validatorFactory,
            $this->validationErrors,
            $rules,
            $messages,
            $customAttributes,
            $callback
        );
    }

}
