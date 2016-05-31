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

use CloudCreativity\LaravelJsonApi\Http\Responses\ResponseFactory;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Document\Error;
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

        return $service->isActive();
    }

    /**
     * @param Request $request
     * @param Exception $e
     * @return Response
     */
    public function renderJsonApi(Request $request, Exception $e)
    {
        /** @var JsonApiService $service */
        $service = app(JsonApiService::class);
        $errors = $this->parseToErrors($e);

        if (!$service->container()->hasEncoder()) {
            return $this->renderWithoutEncoder($errors);
        }

        /** @var ResponseFactory $responses */
        $responses = response()->jsonApi();

        return $responses->errors($errors);
    }

    /**
     * @param Exception $e
     * @return ErrorInterface|ErrorInterface[]
     */
    protected function parseToErrors(Exception $e)
    {
        if ($e instanceof JsonApiException) {
            return $e->getErrors()->getArrayCopy();
        }

        $statusCode = ($e instanceof HttpExceptionInterface) ?
            $e->getStatusCode() :
            Response::HTTP_INTERNAL_SERVER_ERROR;

        $detail = ($e instanceof HttpException) ? $e->getMessage() : null;

        return new Error(null, null, $statusCode, null, null, $detail);
    }

    /**
     * Send a response if no JSON API encoder is available.
     *
     * @param $errors
     * @return Response
     */
    protected function renderWithoutEncoder($errors)
    {
        return response('', Response::HTTP_NOT_ACCEPTABLE);
    }
}
