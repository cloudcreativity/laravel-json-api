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

namespace CloudCreativity\JsonApi\Exceptions;

use Exception;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * Class ValidationException
 *
 * @package CloudCreativity\JsonApi
 */
class ValidationException extends JsonApiException
{

    /**
     * ValidationException constructor.
     *
     * @param ErrorInterface|ErrorInterface[]|ErrorCollection $errors
     * @param string|int|null $defaultHttpCode
     * @param Exception|null $previous
     */
    public function __construct($errors, $defaultHttpCode = self::DEFAULT_HTTP_CODE, Exception $previous = null)
    {
        $errors = MutableErrorCollection::cast($errors);

        parent::__construct($errors, $errors->getHttpStatus($defaultHttpCode), $previous);
    }
}
