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

namespace CloudCreativity\LaravelJsonApi {

    use CloudCreativity\JsonApi\Exceptions\InvalidJsonException;
    use CloudCreativity\LaravelJsonApi\Utils\Helpers;
    use Psr\Http\Message\RequestInterface;
    use Psr\Http\Message\ResponseInterface;

    if (!function_exists('\CloudCreativity\LaravelJsonApi\json_decode')) {

        /**
         * Decodes a JSON string.
         *
         * @param string $content
         * @param bool $assoc
         * @param int $depth
         * @param int $options
         * @return object|array
         * @throws InvalidJsonException
         */
        function json_decode($content, $assoc = false, $depth = 512, $options = 0)
        {
            return Helpers::decode($content, $assoc, $depth, $options);
        }

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
            return $response ? Helpers::doesResponseHaveBody($request, $response) : Helpers::doesRequestHaveBody($request);
        }
    }
}

namespace {

    if (!function_exists('json_api')) {
        /**
         * Get the API handling the inbound request, or a named API.
         *
         * @param string|null $apiName
         *      the API name, or null to get the API handling the inbound request.
         * @return \CloudCreativity\LaravelJsonApi\Api\Api
         * @throws \CloudCreativity\JsonApi\Exceptions\RuntimeException
         */
        function json_api($apiName = null) {
            /** @var \CloudCreativity\LaravelJsonApi\Services\JsonApiService $service */
            $service = app('json-api');

            return $apiName ? $service->api($apiName) : $service->requestApiOrFail();
        }

        /**
         * Get the inbound JSON API request.
         *
         * @return \CloudCreativity\JsonApi\Contracts\Http\Requests\InboundRequestInterface|null
         */
        function json_api_request() {
            /** @var \CloudCreativity\LaravelJsonApi\Services\JsonApiService $service */
            $service = app('json-api');

            return $service->request();
        }
    }
}

