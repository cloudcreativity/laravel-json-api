<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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
use Illuminate\Http\Response;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class NotFoundException extends JsonApiException
{

    /**
     * NotFoundException constructor.
     *
     * @param mixed $errors
     * @param Exception|null $previous
     */
    public function __construct($errors = [], Exception $previous = null)
    {
        parent::__construct($errors, Response::HTTP_NOT_FOUND, $previous);

        $this->addError(Error::create([
            Error::TITLE => 'Not Found',
            Error::STATUS => Response::HTTP_NOT_FOUND,
        ]));
    }
}
