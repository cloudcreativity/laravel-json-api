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

use CloudCreativity\LaravelJsonApi\Contracts\Encoder\SerializerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Exceptions\ExceptionParserInterface;
use CloudCreativity\LaravelJsonApi\Encoder\Encoder;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use CloudCreativity\LaravelJsonApi\Utils\Helpers;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Http\Headers\MediaType;

/**
 * Class HandlerTrait
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait HandlesErrors
{

    /**
     * Does the HTTP request require a JSON API error response?
     *
     * This method determines if we need to render a JSON API error response
     * for the provided exception. We need to do this if:
     *
     * - The client has requested JSON API via its Accept header; or
     * - The application is handling a request to a JSON API endpoint.
     *
     * @param Request $request
     * @param Exception $e
     * @return bool
     */
    public function isJsonApi($request, Exception $e)
    {
        if (Helpers::wantsJsonApi($request)) {
            return true;
        }

        /** @var JsonApiService $service */
        $service = app(JsonApiService::class);

        return !is_null($service->requestApi());
    }

    /**
     * @param Request $request
     * @param Exception $e
     * @return Response
     */
    public function renderJsonApi($request, Exception $e)
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

        /** If there is an active API, use that to send the response. */
        if ($api = $service->requestApi()) {
            return $api->response()->errors($response);
        }

        /** @var SerializerInterface $serializer */
        $serializer = Encoder::instance();

        return response()->json(
            $serializer->serializeErrors($response->getErrors()),
            $response->getHttpCode(),
            ['Content-Type' => MediaType::JSON_API_MEDIA_TYPE]
        );
    }

}
