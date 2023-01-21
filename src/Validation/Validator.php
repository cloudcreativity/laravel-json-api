<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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
use CloudCreativity\LaravelJsonApi\Document\Error\Translator as ErrorTranslator;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Neomerx\JsonApi\Schema\ErrorCollection;

/**
 * Class Validator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Validator implements ValidatorInterface
{

    /**
     * @var ValidatorContract
     */
    protected $validator;

    /**
     * @var ErrorTranslator
     */
    protected $translator;

    /**
     * @var \Closure|null
     */
    protected $callback;

    /**
     * AbstractValidator constructor.
     *
     * @param ValidatorContract $validator
     * @param ErrorTranslator $translator
     * @param \Closure|null $callback
     */
    public function __construct(
        ValidatorContract $validator,
        ErrorTranslator $translator,
        \Closure $callback = null
    ) {
        $this->validator = $validator;
        $this->translator = $translator;
        $this->callback = $callback;
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        return $this->validator->validate();
    }


    /**
     * @inheritdoc
     */
    public function validated()
    {
        return $this->validator->validated();
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
        return $this->translator->failedValidator($this, $this->callback);
    }

}
