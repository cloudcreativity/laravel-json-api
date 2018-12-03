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

use CloudCreativity\LaravelJsonApi\Utils\Helpers;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Trait HandlesErrors
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait HandlesErrors
{

    /**
     * Does the HTTP request require a JSON API error response?
     *
     * This method determines if we need to render a JSON API error response
     * for the client. We need to do this if the client has requested JSON
     * API via its Accept header.
     *
     * @param Request $request
     * @param Exception $e
     * @return bool
     */
    public function isJsonApi($request, Exception $e)
    {
        return Helpers::wantsJsonApi($request);
    }

    /**
     * @param Request $request
     * @param Exception $e
     * @return Response
     */
    public function renderJsonApi($request, Exception $e)
    {
        return json_api()->response()->exception($e);
    }

}
