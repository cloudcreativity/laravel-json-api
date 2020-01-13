<?php

/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Http\Responses;

use CloudCreativity\LaravelJsonApi\Document\Error;
use CloudCreativity\LaravelJsonApi\Http\Responses\ErrorResponse;
use CloudCreativity\LaravelJsonApi\Tests\Unit\TestCase;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * Class ErrorResponseTest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ErrorResponseTest extends TestCase
{

    public function testResolveErrorStatusNoStatus()
    {
        $response = new ErrorResponse(new Error());
        $this->assertEquals(JsonApiException::DEFAULT_HTTP_CODE, $response->getHttpCode());
    }

    public function testResolveErrorStatusUsesDefaultWithMultiple()
    {
        $response = new ErrorResponse([new Error(), new Error()], 499);
        $this->assertEquals(499, $response->getHttpCode());
    }

    public function testResolveErrorStatusUsesErrorStatus()
    {
        $response = new ErrorResponse([new Error(), new Error(null, null, 422)]);
        $this->assertEquals(422, $response->getHttpCode());
    }

    public function testResolveErrorStatus4xx()
    {
        $response = new ErrorResponse([new Error(null, null, 422), new Error(null, null, 415)]);
        $this->assertEquals(400, $response->getHttpCode());
    }

    public function testResolveErrorStatus5xx()
    {
        $response = new ErrorResponse([new Error(null, null, 501), new Error(null, null, 503)]);
        $this->assertEquals(500, $response->getHttpCode());
    }

    public function testResolveErrorStatusMixed()
    {
        $a = new Error(null, null, 422);
        $b = new Error(null, null, 501);
        $response = new ErrorResponse([$a, $b]);

        $this->assertEquals(500, $response->getHttpCode());
        $this->assertSame([$a, $b], $response->getErrors()->getArrayCopy());
    }

    public function testHeaders()
    {
        $headers = ['X-Custom' => 'Foobar'];
        $response = new ErrorResponse([], null, $headers);
        $this->assertEquals($headers, $response->getHeaders());
    }

}
