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

namespace CloudCreativity\LaravelJsonApi\Exceptions;

use CloudCreativity\LaravelJsonApi\Routing\Route;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use CloudCreativity\LaravelJsonApi\Utils\Helpers;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

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
     * @param Throwable $e
     * @return bool
     */
    public function isJsonApi($request, Throwable $e)
    {
        if (Helpers::wantsJsonApi($request)) {
            return true;
        }

        /** @var Route $route */
        $route = app(JsonApiService::class)->currentRoute();

        return $route->hasCodec() && $route->getCodec()->willEncode();
    }

    /**
     * Render an exception as a JSON API error response.
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response
     */
    public function renderJsonApi($request, Throwable $e)
    {
        $headers = ($e instanceof HttpException) ? $e->getHeaders() : [];

        return json_api()->exceptions()->parse($e)->toResponse($request)->withHeaders($headers);
    }

    /**
     * Prepare JSON API exception for non-JSON API rendering.
     *
     * @param JsonApiException $ex
     * @return HttpException
     */
    protected function prepareJsonApiException(JsonApiException $ex)
    {
        $error = Collection::make($ex->getErrors())->map(
            fn(ErrorInterface $err) => $err->getDetail() ?: $err->getTitle()
        )->filter()->first();

        return new HttpException($ex->getHttpCode(), $error, $ex);
    }

}
