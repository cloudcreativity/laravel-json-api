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

namespace DummyApp\Exceptions;

use CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Orchestra\Testbench\Exceptions\Handler as BaseHandler;

class Handler extends BaseHandler
{

    use HandlesErrors;

    /**
     * @var array
     */
    protected $dontReport = [
        JsonApiException::class,
        AuthenticationException::class,
        AuthorizationException::class,
    ];

    /**
     * @param Exception $e
     * @throws Exception
     */
    public function report(Exception $e)
    {
        if ($this->shouldReport($e)) {
            throw $e;
        }
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param \Exception $e
     * @return \Illuminate\Http\Response
     */
    public function render($request, \Exception $e)
    {
        if ($this->isJsonApi()) {
            return $this->renderJsonApi($request, $e);
        }

        return parent::render($request, $e);
    }
}
