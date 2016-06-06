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

use CloudCreativity\JsonApi\Validators\AbstractValidator as BaseValidator;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;

/**
 * Class AbstractValidator
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractValidator extends BaseValidator
{

    /**
     * @var Factory
     */
    protected $validatorFactory;

    /**
     * @var array
     */
    protected $rules;

    /**
     * @var array
     */
    protected $messages;

    /**
     * @var array
     */
    protected $customAttributes;

    /**
     * @var callable|null
     */
    protected $callback;


    /**
     * AttributesValidator constructor.
     * @param ValidatorErrorFactoryInterface $validatorErrorFactory
     * @param Factory $validatorFactory
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @param callable|null $callback
     *      a callback that will be passed the Laravel validator instance when it is made.
     */
    public function __construct(
        ValidatorErrorFactoryInterface $validatorErrorFactory,
        Factory $validatorFactory,
        array $rules,
        array $messages = [],
        array $customAttributes = [],
        callable $callback = null
    ) {
        parent::__construct($validatorErrorFactory);
        $this->validatorFactory = $validatorFactory;
        $this->rules = $rules;
        $this->messages = $messages;
        $this->customAttributes = $customAttributes;
        $this->callback = $callback;
    }

    /**
     * @param array $data
     * @return Validator
     */
    protected function make(array $data)
    {
        $validator = $this->validatorFactory->make(
            $data,
            $this->rules,
            $this->messages,
            $this->customAttributes
        );

        $callback = $this->callback;

        if ($callback) {
            $callback($validator);
        }

        return $validator;
    }
}
