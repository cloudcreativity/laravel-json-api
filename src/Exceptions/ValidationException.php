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

namespace CloudCreativity\LaravelJsonApi\Exceptions;

use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use CloudCreativity\LaravelJsonApi\Utils\Helpers;
use Exception;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Neomerx\JsonApi\Schema\ErrorCollection;

/**
 * Class ValidationException
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ValidationException extends JsonApiException
{

    /**
     * @var ValidatorInterface|null
     */
    private $validator;

    /**
     * Create a validation exception from a validator.
     *
     * @param ValidatorInterface $validator
     * @return ValidationException
     */
    public static function create(ValidatorInterface $validator): self
    {
        $ex = new self($validator->getErrors());
        $ex->validator = $validator;

        return $ex;
    }

    /**
     * ValidationException constructor.
     *
     * @param ErrorInterface|ErrorInterface[]|ErrorCollection $errors
     * @param string|int|null $defaultHttpCode
     * @param Exception|null $previous
     */
    public function __construct($errors, $defaultHttpCode = self::DEFAULT_HTTP_CODE, Exception $previous = null)
    {
        parent::__construct(
            $errors,
            Helpers::httpErrorStatus($errors, $defaultHttpCode),
            $previous
        );
    }

    /**
     * @return ValidatorInterface|null
     */
    public function getValidator(): ?ValidatorInterface
    {
        return $this->validator;
    }
}
