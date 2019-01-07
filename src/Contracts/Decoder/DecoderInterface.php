<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Contracts\Decoder;

use Illuminate\Http\Request;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Interface DecoderInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface DecoderInterface
{

    /**
     * Does the decoder expect request content to comply with the JSON API spec?
     *
     * @return bool
     */
    public function isJsonApi(): bool;

    /**
     * Decode a JSON API document from a request.
     *
     * JSON API request content MUST be decoded as an object, as it is not possible to validate
     * that the request content complies with the JSON API spec if it is JSON decoded to an
     * associative array.
     *
     * If the decoder is unable to return an object when decoding content, it MUST throw
     * a HTTP exception or a JSON API exception.
     *
     * A decoder that returns `false` from its `isJsonApi()` method MUST throw a `LogicException`
     * from this method.
     *
     * @param Request $request
     * @return \stdClass
     *      the JSON API document.
     * @throws HttpException
     * @throws JsonApiException
     * @throws \LogicException
     *      if the decoder does not decode JSON API content.
     */
    public function decode($request): \stdClass;

    /**
     * Return request data as an array.
     *
     * Request data returned from this method is used for validation. If it is
     * JSON API data, it will also be the data passed to adapters.
     *
     * If the decoder is unable to return an array when extracting request data,
     * it MUST throw a HTTP exception or a JSON API exception.
     *
     * @param Request $request
     * @return array
     * @throws HttpException
     * @throws JsonApiException
     */
    public function toArray($request): array;
}
