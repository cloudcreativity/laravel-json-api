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

use Exception;

/**
 * Class DocumentRequiredException
 *
 * Exception to indicate that a JSON API document (in an HTTP request body)
 * is expected but has not been provided.
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class DocumentRequiredException extends InvalidJsonException
{

    /**
     * DocumentRequiredException constructor.
     *
     * @param Exception|null $previous
     */
    public function __construct(Exception $previous = null)
    {
        parent::__construct(
            null,
            'Expecting request to contain a JSON API document.',
            self::HTTP_CODE_BAD_REQUEST,
            $previous
        );
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        return 'Document Required';
    }
}
