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

namespace CloudCreativity\LaravelJsonApi\Validators;

use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\AttributesValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;

/**
 * Class AttributesValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class AttributesValidator extends AbstractValidator implements AttributesValidatorInterface
{

    /**
     * @var ValidatorErrorFactoryInterface
     */
    private $errorFactory;

    /**
     * @var array
     */
    private $rules;

    /**
     * @var array
     */
    private $messages;

    /**
     * @var array
     */
    private $attributes;

    /**
     * @var callable|null
     */
    private $callback;

    /**
     * @var callable|null
     */
    private $extractor;

    /**
     * AttributesValidator constructor.
     *
     * @param Factory $validatorFactory
     * @param ValidatorErrorFactoryInterface $errorFactory
     * @param array $rules
     * @param array $messages
     * @param array $attributes
     * @param callable|null $callback
     * @param callable|null $extractor
     */
    public function __construct(
        Factory $validatorFactory,
        ValidatorErrorFactoryInterface $errorFactory,
        array $rules,
        array $messages = [],
        array $attributes = [],
        callable $callback = null,
        callable $extractor = null
    ) {
        parent::__construct($validatorFactory);
        $this->errorFactory = $errorFactory;
        $this->rules = $rules;
        $this->messages = $messages;
        $this->attributes = $attributes;
        $this->callback = $callback;
        $this->extractor = $extractor;
    }

    /**
     * @inheritdoc
     */
    public function isValid(ResourceObjectInterface $resource, $record = null)
    {
        $attributes = $this->extractAttributes($resource, $record);
        $validator = $this->make($attributes);

        if ($validator->fails()) {
            $this->addValidatorErrors($validator);
            return false;
        }

        return true;
    }

    /**
     * @return array
     */
    protected function getRules()
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    protected function getMessages()
    {
        return $this->messages;
    }

    /**
     * @return array
     */
    protected function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @param Validator $validator
     * @return void
     */
    protected function configureValidator(Validator $validator)
    {
        $callback = $this->callback;

        if ($callback) {
            $callback($validator);
        }
    }

    /**
     * @param ResourceObjectInterface $resource
     * @param object|null $record
     * @return array
     */
    protected function extractAttributes(ResourceObjectInterface $resource, $record = null)
    {
        $extractor = $this->extractor;
        $attributes = ($extractor) ? $extractor($resource, $record) : null;

        return is_array($attributes) ? $attributes : $resource->getAttributes()->toArray();
    }

    /**
     * @param Validator $validator
     */
    protected function addValidatorErrors(Validator $validator)
    {
        $messages = $validator->getMessageBag();
        $this->addErrors($this->errorFactory->resourceInvalidAttributesMessages($messages));
    }

}
