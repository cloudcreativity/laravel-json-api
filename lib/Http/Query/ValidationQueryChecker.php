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

namespace CloudCreativity\JsonApi\Http\Query;

use CloudCreativity\JsonApi\Contracts\Validators\QueryValidatorInterface;
use CloudCreativity\JsonApi\Exceptions\ValidationException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryCheckerInterface;

/**
 * Class ExtendedQueryChecker
 *
 * @package CloudCreativity\JsonApi
 */
class ValidationQueryChecker implements QueryCheckerInterface
{

    /**
     * @var QueryCheckerInterface
     */
    protected $queryChecker;

    /**
     * @var QueryValidatorInterface|null
     */
    protected $queryValidator;

    /**
     * ExtendedQueryChecker constructor.
     *
     * @param QueryCheckerInterface $checker
     * @param QueryValidatorInterface|null $validator
     */
    public function __construct(QueryCheckerInterface $checker, QueryValidatorInterface $validator = null)
    {
        $this->queryChecker = $checker;
        $this->queryValidator = $validator;
    }

    /**
     * @param EncodingParametersInterface $parameters
     */
    public function checkQuery(EncodingParametersInterface $parameters)
    {
        $this->queryChecker->checkQuery($parameters);

        if ($this->queryValidator) {
            $this->validateQuery($parameters);
        }
    }

    /**
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    protected function validateQuery(EncodingParametersInterface $parameters)
    {
        if (!$this->queryValidator->isValid($parameters)) {
            throw new ValidationException(
                $this->queryValidator->getErrors(),
                ValidationException::HTTP_CODE_BAD_REQUEST
            );
        }
    }
}
