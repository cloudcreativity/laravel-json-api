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

namespace CloudCreativity\LaravelJsonApi\Testing;

use CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * Class TestExceptionHandler
 *
 * This exception handler is intended for testing JSON API packages
 * using the `orchestra/testbench` package. It ensures that JSON
 * API exceptions are rendered and if the handler receives any other
 * exceptions, they are re-thrown so that they appear in PHP Unit.
 *
 * Usage in a testbench test case is as follows:
 *
 * ```php
 * protected function resolveApplicationExceptionHandler($app)
 * {
 *   $app->singleton(
 *      \Illuminate\Contracts\Debug\ExceptionHandler::class,
 *      \CloudCreativity\LaravelJsonApi\Testing\TestExceptionHandler::class
 *   );
 * }
 * ```
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class TestExceptionHandler extends ExceptionHandler
{

    use HandlesErrors;

    /**
     * @var array
     * @todo when dropping support for Laravel 5.4, will no longer need to list these framework classes.
     */
    protected $dontReport = [
        \Illuminate\Auth\AuthenticationException::class,
        \Illuminate\Auth\Access\AuthorizationException::class,
        \Symfony\Component\HttpKernel\Exception\HttpException::class,
        \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        \Illuminate\Session\TokenMismatchException::class,
        \Illuminate\Validation\ValidationException::class,
        JsonApiException::class,
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
        if ($this->isJsonApi($request, $e)) {
            return $this->renderJsonApi($request, $e);
        }

        return parent::render($request, $e);
    }
}
