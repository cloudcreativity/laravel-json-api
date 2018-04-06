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

use CloudCreativity\LaravelJsonApi\Utils\ErrorsAwareTrait;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;

/**
 * Class AbstractValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractValidator
{

    use ErrorsAwareTrait;

    /**
     * @var Factory
     */
    private $validatorFactory;

    /**
     * Get validation rules.
     *
     * @return array
     */
    abstract protected function getRules();

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    abstract protected function getMessages();

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    abstract protected function getAttributes();

    /**
     * @param Validator $validator
     * @return void
     */
    abstract protected function configureValidator(Validator $validator);

    /**
     * AttributesValidator constructor.
     *
     * @param Factory $validatorFactory
     */
    public function __construct(Factory $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
    }

    /**
     * Create a validator instance to validate the supplied data.
     *
     * @param array $data
     *      the data to validate.
     * @return Validator
     */
    protected function make(array $data)
    {
        $validator = $this->validatorFactory->make(
            $data,
            $this->getRules(),
            $this->getMessages(),
            $this->getAttributes()
        );

        $this->configureValidator($validator);

        return $validator;
    }
}
