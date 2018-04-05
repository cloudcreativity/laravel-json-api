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

namespace CloudCreativity\JsonApi\Utils;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Class Http
 *
 * @package CloudCreativity\JsonApi
 */
class Http
{

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
     * @param RequestInterface $request
     * @return bool
     */
    public static function doesRequestHaveBody(RequestInterface $request)
    {
        if ($request->hasHeader('Transfer-Encoding')) {
            return true;
        };

        if (!$contentLength = $request->getHeader('Content-Length')) {
            return false;
        }

        return 0 < $contentLength[0];
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
     * @param RequestInterface $request
     * @param ResponseInterface $response
     * @return bool
     */
    public static function doesResponseHaveBody(RequestInterface $request, ResponseInterface $response)
    {
        if ('HEAD' === strtoupper($request->getMethod())) {
            return false;
        }

        $status = $response->getStatusCode();

        if ((100 <= $status && 200 > $status) || 204 === $status || 304 === $status) {
            return false;
        }

        if ($response->hasHeader('Transfer-Encoding')) {
            return true;
        };

        if (!$contentLength = $response->getHeader('Content-Length')) {
            return false;
        }

        return 0 < $contentLength[0];
    }

}
