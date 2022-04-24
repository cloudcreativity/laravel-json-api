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

namespace CloudCreativity\LaravelJsonApi\Utils;

use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidJsonException;
use Illuminate\Support\Str as IlluminateStr;
use Neomerx\JsonApi\Contracts\Schema\ErrorInterface;
use Neomerx\JsonApi\Http\Headers\MediaType;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Helpers
{

    /**
     * Decode a JSON string.
     *
     * @param string $content
     * @param bool $assoc
     * @param int $depth
     * @param int $options
     * @return object|array
     * @throws InvalidJsonException
     */
    public static function decode($content, $assoc = false, $depth = 512, $options = 0)
    {
        $decoded = \json_decode($content, $assoc, $depth, $options);

        if (JSON_ERROR_NONE !== json_last_error()) {
            throw InvalidJsonException::create();
        }

        if (!$assoc && !is_object($decoded)) {
            throw new DocumentRequiredException();
        }

        if ($assoc && !is_array($decoded)) {
            throw new InvalidJsonException(null, 'JSON is not an array.');
        }

        return $decoded;
    }

    /**
     * Does the HTTP request contain body content?
     *
     * "The presence of a message-body in a request is signaled by the inclusion of a Content-Length or
     * Transfer-Encoding header field in the request's message-headers."
     * https://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.3
     *
     * However, some browsers send a Content-Length header with an empty string for e.g. GET requests
     * without any message-body. Therefore rather than checking for the existence of a Content-Length
     * header, we will allow an empty value to indicate that the request does not contain body.
     *
     * @param RequestInterface|SymfonyRequest $request
     * @return bool
     */
    public static function doesRequestHaveBody($request)
    {
        if (self::hasHeader($request, 'Transfer-Encoding')) {
            return true;
        };

        if (1 > self::getHeader($request, 'Content-Length')) {
            return false;
        }

        return true;
    }

    /**
     * Does the HTTP response contain body content?
     *
     * "For response messages, whether or not a message-body is included with a message is dependent
     * on both the request method and the response status code (section 6.1.1). All responses to the
     * HEAD request method MUST NOT include a message-body, even though the presence of entity-header
     * fields might lead one to believe they do. All 1xx (informational), 204 (no content), and 304
     * (not modified) responses MUST NOT include a message-body. All other responses do include a
     * message-body, although it MAY be of zero length."
     * https://www.w3.org/Protocols/rfc2616/rfc2616-sec4.html#sec4.3
     *
     * @param RequestInterface|SymfonyRequest $request
     * @param ResponseInterface|SymfonyResponse $response
     * @return bool
     */
    public static function doesResponseHaveBody($request, $response)
    {
        if ('HEAD' === strtoupper($request->getMethod())) {
            return false;
        }

        $status = $response->getStatusCode();

        if ((100 <= $status && 200 > $status) || 204 === $status || 304 === $status) {
            return false;
        }

        if (self::hasHeader($response, 'Transfer-Encoding')) {
            return true;
        };

        if (!$contentLength = self::getHeader($response, 'Content-Length')) {
            return false;
        }

        return 0 < $contentLength[0];
    }

    /**
     * Does the request want JSON API content?
     *
     * @param SymfonyRequest $request
     * @return bool
     */
    public static function wantsJsonApi($request)
    {
        $acceptable = $request->getAcceptableContentTypes();

        return isset($acceptable[0]) && IlluminateStr::contains($acceptable[0], MediaType::JSON_API_SUB_TYPE);
    }

    /**
     * Has the request sent JSON API content?
     *
     * @param SymfonyRequest $request
     * @return bool
     */
    public static function isJsonApi($request)
    {
        return IlluminateStr::contains($request->headers->get('Content-Type'), MediaType::JSON_API_SUB_TYPE);
    }

    /**
     * Get the most applicable HTTP status code.
     *
     * When a server encounters multiple problems for a single request, the most generally applicable HTTP error
     * code SHOULD be used in the response. For instance, 400 Bad Request might be appropriate for multiple
     * 4xx errors or 500 Internal Server Error might be appropriate for multiple 5xx errors.
     *
     * @param iterable|ErrorInterface $errors
     * @param int|null $default
     * @return int
     * @see https://jsonapi.org/format/#errors
     * @deprecated 3.0.0 use `Document\Error\Errors::getStatus()`
     */
    public static function httpErrorStatus($errors, int $default = null): int
    {
        if (\is_null($default)) {
            $default = SymfonyResponse::HTTP_BAD_REQUEST;
        }

        if ($errors instanceof ErrorInterface) {
            $errors = [$errors];
        }

        $statuses = collect($errors)->reject(function (ErrorInterface $error) {
            return is_null($error->getStatus());
        })->map(function (ErrorInterface $error) {
            return (int) $error->getStatus();
        })->unique();

        if (2 > count($statuses)) {
            return $statuses->first() ?: $default;
        }

        $only4xx = $statuses->every(function (int $status) {
            return 400 <= $status && 499 >= $status;
        });

        return $only4xx ? SymfonyResponse::HTTP_BAD_REQUEST : SymfonyResponse::HTTP_INTERNAL_SERVER_ERROR;
    }

    /**
     * @param MessageInterface|SymfonyRequest|SymfonyResponse $message
     * @param $key
     * @return mixed
     */
    private static function getHeader($message, $key)
    {
        if ($message instanceof MessageInterface) {
            return $message->getHeader($key)[0] ?? null;
        }

        return $message->headers->get($key);
    }

    /**
     * @param MessageInterface|SymfonyRequest|SymfonyResponse $message
     * @param $key
     * @return mixed
     */
    private static function hasHeader($message, $key)
    {
        if ($message instanceof MessageInterface) {
            return $message->hasHeader($key);
        }

        return $message->headers->has($key);
    }

}
