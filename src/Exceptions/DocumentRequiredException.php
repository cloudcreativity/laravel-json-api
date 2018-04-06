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

namespace CloudCreativity\LaravelJsonApi\Exceptions;

use CloudCreativity\LaravelJsonApi\Document\Error;
use Exception;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * Class DocumentRequiredException
 *
 * Exception to indicate that a JSON API document (in an HTTP request body)
 * is expected but has not been provided.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class DocumentRequiredException extends JsonApiException
{

    /**
     * DocumentRequiredException constructor.
     *
     * @param $errors
     * @param Exception|null $previous
     */
    public function __construct($errors = [], Exception $previous = null)
    {
        parent::__construct($errors, self::HTTP_CODE_BAD_REQUEST, $previous);

        $this->addError(Error::create([
            Error::TITLE => 'Document Required',
            Error::STATUS => self::HTTP_CODE_BAD_REQUEST,
            Error::DETAIL => 'Expecting request to contain a JSON API document.',
        ]));
    }
}
