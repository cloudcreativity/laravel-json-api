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

namespace CloudCreativity\JsonApi\Validators;

use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Contracts\Validators\AcceptRelatedResourceInterface;
use CloudCreativity\JsonApi\Contracts\Validators\AttributesValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ResourceValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorFactoryInterface;

/**
 * Class ValidatorFactory
 *
 * @package CloudCreativity\JsonApi
 */
class ValidatorFactory implements ValidatorFactoryInterface
{

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
     */
    public function __construct(ValidatorErrorFactoryInterface $validationErrors, StoreInterface $store)
    {
        $this->validationErrors = $validationErrors;
        $this->store = $store;
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
}
