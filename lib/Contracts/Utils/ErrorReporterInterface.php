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

namespace CloudCreativity\JsonApi\Contracts\Utils;

use CloudCreativity\JsonApi\Contracts\Http\Responses\ErrorResponseInterface;
use Exception;

/**
 * Interface ErrorReporterInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface ErrorReporterInterface
{

    /**
     * Report/log a JSON API error response that will be sent to a client.
     *
     * @param ErrorResponseInterface $response
     *      the error response that will be sent to the client.
     * @param Exception $e
     *      the exception that generated the response, or null if not generated from an exception.
     * @return void
     */
    public function report(ErrorResponseInterface $response, Exception $e = null);
}
