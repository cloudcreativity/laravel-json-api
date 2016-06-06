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

use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Contracts\Validators\AttributesValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\FilterValidatorInterface;
use CloudCreativity\JsonApi\Validators\ValidatorFactory as BaseFactory;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorFactoryInterface;
use Illuminate\Contracts\Validation\Factory;

/**
 * Class ValidatorFactory
 * @package CloudCreativity\LaravelJsonApi
 */
class ValidatorFactory extends BaseFactory implements ValidatorFactoryInterface
{

    /**
     * ValidatorFactory constructor.
     * @param ValidatorErrorFactoryInterface $validationErrors
     * @param StoreInterface $store
     */
    public function __construct(
        ValidatorErrorFactoryInterface $validationErrors,
        StoreInterface $store
    ) {
        parent::__construct($validationErrors, $store);
    }

    /**
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @param callable $callback
     *      a callback that will be called with the Laravel validator instance when it is made.
     * @return AttributesValidatorInterface
     */
    public function attributes(
        array $rules,
        array $messages = [],
        array $customAttributes = [],
        callable $callback = null
    ) {
        /** @var Factory $factory */
        $factory = app(Factory::class);
        /** @var ValidatorErrorFactoryInterface $validationErrors */
        $validationErrors = $this->validationErrors;

        return new AttributesValidator(
            $validationErrors,
            $factory,
            $rules,
            $messages,
            $customAttributes,
            $callback
        );
    }

    /**
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @param callable|null $callback
     * @return FilterValidatorInterface
     */
    public function filterParams(
        array $rules,
        array $messages = [],
        array $customAttributes = [],
        callable $callback = null
    ) {
        /** @var Factory $factory */
        $factory = app(Factory::class);
        /** @var ValidatorErrorFactoryInterface $validationErrors */
        $validationErrors = $this->validationErrors;

        return new FilterValidator(
            $validationErrors,
            $factory,
            $rules,
            $messages,
            $customAttributes,
            $callback
        );
    }

}
