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

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\ValidationException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryCheckerInterface;

/**
 * Class QueryChecker
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class QueryChecker implements QueryCheckerInterface
{

    /**
     * @var QueryCheckerInterface
     */
    protected $checker;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * ExtendedQueryChecker constructor.
     *
     * @param QueryCheckerInterface $checker
     * @param ValidatorInterface $validator
     */
    public function __construct(QueryCheckerInterface $checker, ValidatorInterface $validator)
    {
        $this->checker = $checker;
        $this->validator = $validator;
    }

    /**
     * @param EncodingParametersInterface $parameters
     */
    public function checkQuery(EncodingParametersInterface $parameters)
    {
        $this->checker->checkQuery($parameters);
        $this->validateQuery();
    }

    /**
     * @return void
     */
    protected function validateQuery()
    {
        if ($this->validator->fails()) {
            throw new ValidationException(
                $this->validator->getErrors(),
                ValidationException::HTTP_CODE_BAD_REQUEST
            );
        }
    }
}
