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

namespace CloudCreativity\LaravelJsonApi {

    use CloudCreativity\LaravelJsonApi\Exceptions\InvalidJsonException;
    use CloudCreativity\LaravelJsonApi\Utils\Helpers;
    use Psr\Http\Message\RequestInterface;
    use Psr\Http\Message\ResponseInterface;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\HttpFoundation\Response;

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
         * @param RequestInterface|Request $request
         * @param ResponseInterface|Response $response
         * @return bool
         */
        function http_contains_body($request, $response = null)
        {
            return $response ?
                Helpers::doesResponseHaveBody($request, $response) :
                Helpers::doesRequestHaveBody($request);
        }
    }
}

namespace {

    use CloudCreativity\LaravelJsonApi\Api\Api;
    use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;

    if (!function_exists('json_api')) {
        /**
         * Get a named API, the API handling the inbound request or the default API.
         *
         * @param string|null $apiName
         *      the API name, or null to get either the API handling the inbound request or the default.
         * @param string|null $host
         *      the host to use, or null to use the API's configured settings.
         * @param array $parameters
         *      route parameters to use for the API namespace.
         * @return Api
         * @throws RuntimeException
         */
        function json_api($apiName = null, $host = null, array $parameters = []) {
            if ($apiName) {
                return app('json-api')->api($apiName, $host, $parameters);
            }

            return app('json-api')->requestApiOrDefault($host, $parameters);
        }
    }
}

