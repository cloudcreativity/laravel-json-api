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

use CloudCreativity\JsonApi\Contracts\Object\RelationshipsInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorFactoryInterface;
use CloudCreativity\JsonApi\Utils\ErrorsAwareTrait;
use CloudCreativity\JsonApi\Utils\Pointer as P;

/**
 * Class RelationshipsValidator
 *
 * @package CloudCreativity\JsonApi
 */
class RelationshipsValidator implements RelationshipsValidatorInterface
{

    use ErrorsAwareTrait;

    /**
     * @var ValidatorErrorFactoryInterface
     */
    private $errorFactory;

    /**
     * @var ValidatorFactoryInterface
     */
    private $factory;

    /**
     * @var array
     */
    private $stack = [];

    /**
     * @var string[]
     */
    private $required = [];

    /**
     * RelationshipsValidator constructor.
     *
     * @param ValidatorErrorFactoryInterface $errorFactory
     * @param ValidatorFactoryInterface $factory
     */
    public function __construct(ValidatorErrorFactoryInterface $errorFactory, ValidatorFactoryInterface $factory)
    {
        $this->errorFactory = $errorFactory;
        $this->factory = $factory;
    }

    /**
     * @inheritdoc
     */
    public function add($key, RelationshipValidatorInterface $validator)
    {
        $this->stack[$key] = $validator;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function get($key)
    {
        $validator = isset($this->stack[$key]) ? $this->stack[$key] : null;

        if (!$validator) {
            $validator = $this->factory->relationship();
        }

        return $validator;
    }

    /**
     * @inheritdoc
     */
    public function hasOne(
        $key,
        $expectedType = null,
        $required = false,
        $allowEmpty = true,
        $acceptable = null
    ) {
        $expectedType = $expectedType ?: $key;

        $this->add($key, $this->factory->hasOne(
            $expectedType,
            $allowEmpty,
            $acceptable
        ));

        if ($required) {
            $this->required[] = $key;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function hasMany(
        $key,
        $expectedType = null,
        $required = false,
        $allowEmpty = false,
        $acceptable = null
    ) {
        $expectedType = $expectedType ?: $key;

        $this->add($key, $this->factory->hasMany(
            $expectedType,
            $allowEmpty,
            $acceptable
        ));

        if ($required) {
            $this->required[] = $key;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function isValid(ResourceObjectInterface $resource, $record = null)
    {
        $relationships = $resource->getRelationships();
        $valid = true;

        if (!$this->validateRequired($relationships)) {
            $valid = false;
        }

        foreach ($relationships->keys() as $key) {
            if (!$this->validateRelationship($key, $relationships, $resource, $record)) {
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * @param RelationshipsInterface $relationships
     * @return bool
     */
    protected function validateRequired(RelationshipsInterface $relationships)
    {
        $valid = true;

        foreach ($this->required as $key) {

            if (!$relationships->has($key)) {
                $this->addError($this->errorFactory->memberRequired(
                    $key,
                    P::relationships()
                ));
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * @param $key
     * @param RelationshipsInterface $relationships
     * @param ResourceObjectInterface $resource
     * @param object|null $record
     * @return bool
     */
    protected function validateRelationship(
        $key,
        RelationshipsInterface $relationships,
        ResourceObjectInterface $resource,
        $record = null
    ) {
        if (!is_object($relationships->get($key))) {
            $this->addError($this->errorFactory->memberObjectExpected(
                $key,
                P::relationship($key)
            ));
            return false;
        }

        $validator = $this->get($key);
        $relationship = $relationships->getRelationship($key);

        if (!$validator->isValid($relationship, $record, $key, $resource)) {
            $this->addErrors($validator->getErrors());
            return false;
        }

        return true;
    }
}
