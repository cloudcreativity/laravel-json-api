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

use CloudCreativity\LaravelJsonApi\Contracts\Validators\QueryValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use Illuminate\Contracts\Validation\Factory;
use Illuminate\Contracts\Validation\Validator;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;

/**
 * Class QueryValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0 use classes in the `Validation` namespace instead.
 */
class QueryValidator extends AbstractValidator implements QueryValidatorInterface
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
     * AttributesValidator constructor.
     *
     * @param Factory $validatorFactory
     * @param ValidatorErrorFactoryInterface $errorFactory
     * @param array $rules
     * @param array $messages
     * @param array $attributes
     * @param callable|null $callback
     */
    public function __construct(
        Factory $validatorFactory,
        ValidatorErrorFactoryInterface $errorFactory,
        array $rules,
        array $messages = [],
        array $attributes = [],
        callable $callback = null
    ) {
        parent::__construct($validatorFactory);
        $this->errorFactory = $errorFactory;
        $this->rules = $rules;
        $this->messages = $messages;
        $this->attributes = $attributes;
        $this->callback = $callback;
    }

    /**
     * @inheritdoc
     */
    public function isValid(EncodingParametersInterface $parameters)
    {
        $validator = $this->make($this->extract($parameters));

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
     * @param EncodingParametersInterface $parameters
     * @return array
     */
    protected function extract(EncodingParametersInterface $parameters)
    {
        $data = (array) $parameters->getUnrecognizedParameters();
        $data[QueryParametersParserInterface::PARAM_FILTER] = (array) $parameters->getFilteringParameters();
        $data[QueryParametersParserInterface::PARAM_PAGE] = (array) $parameters->getPaginationParameters();

        return $data;
    }

    /**
     * @param Validator $validator
     */
    protected function addValidatorErrors(Validator $validator)
    {
        $messages = $validator->getMessageBag();
        $this->addErrors($this->errorFactory->queryParametersMessages($messages));
    }

}
