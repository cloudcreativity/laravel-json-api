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
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

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
     * @var \Closure|null
     */
    protected $callback;

    /**
     * AbstractValidator constructor.
     *
     * @param ValidatorContract $validator
     * @param \Closure|null $callback
     */
    public function __construct(ValidatorContract $validator, \Closure $callback = null)
    {
        $this->validator = $validator;
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
        $failed = $this->failed();
        $errors = new ErrorCollection();

        foreach ($this->errors()->messages() as $key => $messages) {
            $failures = $this->createFailures($failed[$key] ?? []);

            foreach ($messages as $detail) {
                $failed = $failures->shift() ?: [];
                $errors->add($this->createError($key, $detail, $failed));
            }
        }

        return $errors;
    }

    /**
     * @param string $key
     * @param string $detail
     * @param array $failed
     * @return ErrorInterface
     */
    protected function createError(string $key, string $detail, array $failed): ErrorInterface
    {
        if ($fn = $this->callback) {
            return $fn($key, $detail, $failed);
        }

        return new Error(
            null,
            null,
            '422',
            null,
            'Unprocessable Entity',
            $detail
        );
    }

    /**
     * @param array $failures
     * @return Collection
     */
    protected function createFailures(array $failures): Collection
    {
        return collect($failures)->map(function ($options, $rule) {
            return array_filter(['rule' => $rule, 'options' => $options ?: null]);
        })->values();
    }

}
