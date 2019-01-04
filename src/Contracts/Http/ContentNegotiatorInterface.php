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

namespace CloudCreativity\LaravelJsonApi\Contracts\Http;

use CloudCreativity\LaravelJsonApi\Api\Api;
use CloudCreativity\LaravelJsonApi\Api\Codec;
use CloudCreativity\LaravelJsonApi\Api\Codecs;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Interface ContentNegotiatorInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 * @see http://jsonapi.org/format/#content-negotiation
 */
interface ContentNegotiatorInterface
{

    const HTTP_NOT_ACCEPTABLE = Response::HTTP_NOT_ACCEPTABLE;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = Response::HTTP_UNSUPPORTED_MEDIA_TYPE;

    /**
     * Negotiate content for a fetch resource request.
     *
     * E.g. for a `posts` resource, this is invoked on the following URLs:
     *
     * - `POST /posts`
     * - `GET /posts/1`
     * - `PATCH /posts/1`
     * - `DELETE /posts/1`
     *
     * I.e. a response that will contain a specific resource.
     *
     * @param Codecs $codecs
     *      the default codecs for the API.
     * @param Request $request
     *      the request.
     * @param mixed|null $record
     *      the domain record (if it already exists).
     * @return Codec
     *      the matching codec.
     * @throws HttpException
     * @throws JsonApiException
     */
    public function negotiate(Codecs $codecs, $request, $record = null): Codec;

    /**
     * Negotiate content for a fetch many request.
     *
     * E.g. for a `posts` resource, this is invoked on the following URLs:
     *
     * - `/posts`
     * - `/comments/1/posts`
     *
     * I.e. a response that will contain zero to many of the posts resource.
     *
     * @param Codecs $codecs
     *      the default codecs for the API.
     * @param Request $request
     *      the request.
     * @return Codec
     *      the matching codec.
     * @throws HttpException
     * @throws JsonApiException
     */
    public function negotiateMany(Codecs $codecs, $request): Codec;

}
