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

namespace CloudCreativity\LaravelJsonApi\Exceptions;

use CloudCreativity\JsonApi\Contracts\Exceptions\ExceptionParserInterface;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Class HandlerTrait
 *
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

        return !is_null($service->requestApi());
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
        /** @var ExceptionParserInterface $handler */
        $handler = app(ExceptionParserInterface::class);

        $response = $handler->parse($e);
        $service->report($response, $e);

        /** Client does not accept a JSON API response. */
        if (Response::HTTP_NOT_ACCEPTABLE === $response->getHttpCode()) {
            return response('', Response::HTTP_NOT_ACCEPTABLE);
        }

        return $service
            ->requestApi()
            ->response()
            ->errors($response);
    }

}
