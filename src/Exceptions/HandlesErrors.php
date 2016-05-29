<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Exception;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Http\ResponsesInterface;
use Neomerx\JsonApi\Document\Error;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

/**
 * Class HandlerTrait
 * @package CloudCreativity\LaravelJsonApi
 */
trait HandlesErrors
{

    /**
     * @return bool
     */
    public function isJsonApi()
    {
        /** @var JsonApiService $service */
        $service = app(JsonApiService::class);

        return $service->isJsonApi();
    }

    /**
     * @param Exception $ex
     * @return Response
     */
    public function renderJsonApi(Exception $ex)
    {
        /** @var ResponsesInterface $responses */
        $responses = response()->jsonApi();
        $errors = $this->parseToJsonApi($ex);

        return $responses->getErrorResponse($errors);
    }

    /**
     * @param Exception $ex
     * @return Error|ErrorCollection
     */
    protected function parseToJsonApi(Exception $ex)
    {
        if ($ex instanceof JsonApiException) {
            return $ex->getErrors();
        }

        $statusCode = ($ex instanceof HttpExceptionInterface) ? $ex->getStatusCode() : null;
        $detail = ($ex instanceof HttpException) ? $ex->getMessage() : null;

        return new Error(null, null, $statusCode, null, null, $detail);
    }
}
