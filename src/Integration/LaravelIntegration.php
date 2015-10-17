<?php

/**
 * Copyright 2015 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Integration;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Integration\CurrentRequestInterface;
use Neomerx\JsonApi\Contracts\Integration\NativeResponsesInterface;

/**
 * Class LaravelIntegration
 * @package CloudCreativity\JsonApi\Laravel
 */
class LaravelIntegration implements CurrentRequestInterface, NativeResponsesInterface
{

    /**
     * @var Request
     */
    private $_request;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->_request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->_request;
    }

    /**
     * Get content.
     * @return string|null
     */
    public function getContent()
    {
        $content = $this
            ->getRequest()
            ->getContent();

        return !empty($content) ? $content : null;
    }

    /**
     * Get inputs.
     * @return array
     */
    public function getQueryParameters()
    {
        return $this
            ->getRequest()
            ->query();
    }

    /**
     * Get header value.
     *
     * @param string $name
     * @return string|null
     */
    public function getHeader($name)
    {
        return $this
            ->getRequest()
            ->header($name, null);
    }

    /**
     * Create HTTP response.
     *
     * @param string|null $content
     * @param int $statusCode
     * @param array $headers
     *
     * @return Response
     */
    public function createResponse($content, $statusCode, array $headers)
    {
        return new Response($content, $statusCode, $headers);
    }
}
