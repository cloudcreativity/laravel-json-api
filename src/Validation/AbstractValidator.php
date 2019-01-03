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

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * Class AbstractValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractValidator implements ValidatorInterface
{

    /**
     * @var ValidatorContract
     */
    protected $validator;

    /**
     * @var ErrorTranslator
     */
    protected $errors;

    /**
     * @param string $key
     * @param string $detail
     * @return ErrorInterface
     */
    abstract protected function createError(string $key, string $detail): ErrorInterface;

    /**
     * AbstractValidator constructor.
     *
     * @param ValidatorContract $validator
     * @param ErrorTranslator $errors
     */
    public function __construct(ValidatorContract $validator, ErrorTranslator $errors)
    {
        $this->validator = $validator;
        $this->errors = $errors;
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        return $this->validator->validate();
    }

    /**
     * @inheritDoc
     */
    public function fails()
    {
        return $this->validator->fails();
    }

    /**
     * @inheritDoc
     */
    public function failed()
    {
        return $this->validator->failed();
    }

    /**
     * @inheritDoc
     */
    public function sometimes($attribute, $rules, callable $callback)
    {
        return $this->validator->sometimes($attribute, $rules, $callback);
    }

    /**
     * @inheritDoc
     */
    public function after($callback)
    {
        return $this->validator->after($callback);
    }

    /**
     * @inheritDoc
     */
    public function errors()
    {
        return $this->validator->errors();
    }

    /**
     * @inheritDoc
     */
    public function getMessageBag()
    {
        return $this->validator->getMessageBag();
    }

    /**
     * @inheritdoc
     */
    public function getErrors(): ErrorCollection
    {
        $errors = new ErrorCollection();

        foreach ($this->getMessageBag()->toArray() as $key => $messages) {
            foreach ($messages as $detail) {
                $errors->add($this->createError($key, $detail));
            }
        }

        return $errors;
    }

}
