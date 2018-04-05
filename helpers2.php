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

namespace CloudCreativity\JsonApi;

use CloudCreativity\JsonApi\Exceptions\InvalidJsonException;
use CloudCreativity\JsonApi\Utils\Http;
use Neomerx\JsonApi\Exceptions\JsonApiException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

if (!function_exists('CloudCreativity\JsonApi\json_decode')) {
    /**
     * Decodes a JSON string.
     *
     * @param $content
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @return mixed
     * @throws JsonApiException
     */
    function json_decode($content, $assoc = false, $depth = 512, $options = 0)
    {
        $decoded = \json_decode($content, $assoc, $depth, $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw InvalidJsonException::create();
        }

        if (!$assoc && !is_object($decoded)) {
            throw new InvalidJsonException(null, 'JSON is not an object.');
        }

        if ($assoc && !is_array($decoded)) {
            throw new InvalidJsonException(null, 'JSON is not an object or array.');
        }

        return $decoded;
    }
}

if (!function_exists('CloudCreativity\JsonApi\http_contains_body')) {
    /**
     * Does the HTTP message contain body content?
     *
     * If only a request is provided, the method will determine if the request contains body.
     *
     * If a request and response is provided, the method will determine if the response contains
     * body. Determining this for a response is dependent on the request method, which is why
     * the request is also required.
     *
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    function http_contains_body(RequestInterface $request, ResponseInterface $response = null)
    {
        return $response ? Http::doesResponseHaveBody($request, $response) : Http::doesRequestHaveBody($request);
    }
}
