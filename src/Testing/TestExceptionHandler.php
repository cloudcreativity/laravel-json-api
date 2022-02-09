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

namespace CloudCreativity\LaravelJsonApi\Testing;

use CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Throwable;

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
     */
    protected $dontReport = [
        JsonApiException::class,
    ];

    /**
     * @param Throwable $e
     * @throws Exception
     */
    public function report(Throwable $e)
    {
        // no-op
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param Throwable $e
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Throwable $e)
    {
        if ($this->isJsonApi($request, $e)) {
            return $this->renderJsonApi($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * @param Throwable $e
     * @return Throwable
     */
    protected function prepareException(Throwable $e)
    {
        if ($e instanceof JsonApiException) {
            return $this->prepareJsonApiException($e);
        }

        return parent::prepareException($e);
    }
}
