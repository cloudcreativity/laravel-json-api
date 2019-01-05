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

use CloudCreativity\LaravelJsonApi\Api\Codec;
use CloudCreativity\LaravelJsonApi\Api\Codecs;
use CloudCreativity\LaravelJsonApi\Http\Decoder;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Http\Headers\AcceptHeaderInterface;
use Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface;
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
     * Get a codec for a resource response.
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
     * @param AcceptHeaderInterface $header
     *      the Accept header provided by the client.
     * @param Codecs $codecs
     *      the default codecs for the API.
     * @param mixed|null $record
     *      the domain record the request relates to, unless one is being created.
     * @return Codec
     *      the matching codec.
     * @throws HttpException
     * @throws JsonApiException
     */
    public function codec(AcceptHeaderInterface $header, Codecs $codecs, $record = null): Codec;

    /**
     * Get a codec for a zero-to-many resources response.
     *
     * E.g. for a `posts` resource, this is invoked on the following URLs:
     *
     * - `/posts`
     * - `/comments/1/post`
     * - `/users/123/posts`
     *
     * I.e. a response that will contain zero to many of the posts resource.
     *
     * @param AcceptHeaderInterface $header
     *      the Accept header provided by the client.
     * @param Codecs $codecs
     *      the default codecs for the API.
     * @return Codec
     *      the matching codec.
     * @throws HttpException
     * @throws JsonApiException
     */
    public function codecForMany(AcceptHeaderInterface $header, Codecs $codecs): Codec;

    /**
     * Get a decoder for a request that contains content.
     *
     * E.g. for the `posts` resource, this is invoked on the following:
     *
     * - `POST /posts`
     * - `PATCH /posts/1`
     * - `POST /posts/1/tags`
     * - `PATCH /posts/1/tags`
     * - `DELETE /posts/1/tags`
     *
     * @param HeaderInterface $header
     *      the Content-Type header provided by the client.
     * @param mixed|null $record
     *      the domain record the request relates to, unless one is being created.
     * @return Decoder
     */
    public function decoder(HeaderInterface $header, $record = null): Decoder;

}
