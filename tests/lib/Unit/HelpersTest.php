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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit;

use CloudCreativity\LaravelJsonApi\Exceptions\InvalidJsonException;
use CloudCreativity\LaravelJsonApi\Utils\Helpers;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Http\Request as IlluminateRequest;
use Illuminate\Http\Response as IlluminateResponse;
use Neomerx\JsonApi\Document\Error;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use function CloudCreativity\LaravelJsonApi\http_contains_body;
use function CloudCreativity\LaravelJsonApi\json_decode;

class HelpersTest extends TestCase
{

    /**
     * @return array
     */
    public function invalidJsonProvider()
    {
        return [
            'parse error' => ['{ "data": { "type": "foo" }', true],
            'empty string' => [''],
            'null' => ['NULL'],
            'integer' => ['1'],
            'bool' => ['true'],
            'string' => ['foo'],
        ];
    }

    /**
     * @param $content
     * @param bool $jsonError
     * @dataProvider invalidJsonProvider
     */
    public function testInvalidJson($content, $jsonError = false)
    {
        $actual = null;

        try {
            json_decode($content);
        } catch (InvalidJsonException $ex) {
            $actual = $ex;
        }

        $this->assertNotNull($actual);

        if ($jsonError) {
            $this->assertJsonError($ex);
        }
    }

    /**
     * @return array
     */
    public function requestContainsBodyProvider()
    {
        return [
            'neither header' => [[], false],
            'content-length' => [['Content-Length' => '120'], true],
            'zero content-length' => [['Content-Length' => '0'], false],
            'empty content-length' => [['Content-Length' => ''], false],
            'transfer-encoding 1' => [['Transfer-Encoding' => 'chunked'], true],
            'transfer-encoding 2' => [['Transfer-Encoding' => 'gzip, chunked'], true],
            'content-type no content-length' => [['Content-Type' => 'text/plain'], false],
        ];
    }

    /**
     * @param array $headers
     * @param $expected
     * @dataProvider requestContainsBodyProvider
     */
    public function testRequestContainsBody(array $headers, $expected)
    {
        $request = new Request('GET', '/api/posts', $headers);

        $this->assertSame($expected, http_contains_body($request));
    }

    /**
     * @param array $headers
     * @param $expected
     * @dataProvider requestContainsBodyProvider
     */
    public function testIlluminateRequestContainsBody(array $headers, $expected)
    {
        $request = IlluminateRequest::create('/api/posts', 'GET');

        foreach ($headers as $header => $value) {
            $request->headers->set($header, $value);
        }

        $this->assertSame($expected, http_contains_body($request));
    }

    /**
     * @param array $headers
     * @param $expected
     * @dataProvider requestContainsBodyProvider
     */
    public function testSymfonyRequestContainsBody(array $headers, $expected)
    {
        $request = SymfonyRequest::create('/api/posts', 'GET');

        foreach ($headers as $header => $value) {
            $request->headers->set($header, $value);
        }

        $this->assertSame($expected, http_contains_body($request));
    }

    /**
     * @return array
     */
    public function responseContainsBodyProvider()
    {
        return [
            'head never contains body' => [false, 'HEAD', 200],
            '1xx never contain body' => [false, 'POST', 100],
            '204 never contains body' => [false, 'GET', 204],
            '304 never contains body' => [false, 'GET', 304],
            '200 with zero content length' => [false, 'GET', 200, ['Content-Length' => '0']],
            '200 with content' => [true, 'GET', 200, ['Content-Length' => '3'], 'foo'],
            '201 with content' => [true, 'POST', 201, ['Content-Length' => '3'], 'foo'],
            '200 with transfer encoding' => [true, 'GET', 200, ['Transfer-Encoding' => 'chunked']],
        ];
    }

    /**
     * @param $expected
     * @param $method
     * @param $status
     * @param array $headers
     * @param $body
     * @dataProvider responseContainsBodyProvider
     */
    public function testResponseContainsBody($expected, $method, $status, $headers = [], $body = null)
    {
        $request = new Request($method, '/api/posts');
        $response = new Response($status, $headers, $body);

        $this->assertSame($expected, http_contains_body($request, $response));
    }

    /**
     * @param $expected
     * @param $method
     * @param $status
     * @param array $headers
     * @param $body
     * @dataProvider responseContainsBodyProvider
     */
    public function testIlluminateResponseContainsBody($expected, $method, $status, $headers = [], $body = null)
    {
        $request = IlluminateRequest::create('/api/posts', $method);
        $response = new IlluminateResponse($body, $status, $headers);

        $this->assertSame($expected, http_contains_body($request, $response));
    }

    /**
     * @param $expected
     * @param $method
     * @param $status
     * @param array $headers
     * @param $body
     * @dataProvider responseContainsBodyProvider
     */
    public function testSymfonyResponseContainsBody($expected, $method, $status, $headers = [], $body = null)
    {
        $request = SymfonyRequest::create('/api/posts', $method);
        $response = new SymfonyResponse($body, $status, $headers);

        $this->assertSame($expected, http_contains_body($request, $response));
    }

    /**
     * @return array
     */
    public function mediaTypesProvider()
    {
        return [
            ['application/vnd.api+json', true],
            ['application/json', false],
            ['text/html', false],
        ];
    }

    /**
     * @param $accept
     * @param $expected
     * @dataProvider mediaTypesProvider
     */
    public function testWantsJsonApi($accept, $expected)
    {
        $request = new IlluminateRequest();
        $request->headers->set('Accept', $accept);

        $this->assertSame($expected, Helpers::wantsJsonApi($request));
    }

    /**
     * @param $contentType
     * @param $expected
     * @dataProvider mediaTypesProvider
     */
    public function testIsJsonApi($contentType, $expected)
    {
        $request = new IlluminateRequest();
        $request->headers->set('Content-Type', $contentType);

        $this->assertSame($expected, Helpers::isJsonApi($request));
    }

    /**
     * @return array
     */
    public function httpErrorProvider(): array
    {
        return [
            'empty' => [
                [],
                400,
            ],
            'only 4xx' => [
                [400, 422, 419],
                400,
            ],
            'only 422' => [
                [422, 422],
                422,
            ],
            'only 5xx' => [
                [500, 502],
                500,
            ],
            'only 502' => [
                [502, 502],
                502,
            ],
            'only null' => [
                [null, null],
                400,
            ],
            '4xx with null' => [
                [422, 419, null],
                400,
            ],
            '422 with null' => [
                [422, 422, null],
                422,
            ],
            '5xx with null' => [
                [null, 500, 502],
                500,
            ],
            '502 with null' => [
                [502, null, 502],
                502,
            ],
        ];
    }

    /**
     * @param array $errors
     * @param int $expected
     * @dataProvider httpErrorProvider
     */
    public function testHttpErrorStatus(array $errors, int $expected): void
    {
        $errors = collect($errors)->map(function (?int $status) {
            return new Error(null, null, $status);
        })->all();

        $this->assertSame(Helpers::httpErrorStatus($errors), $expected);
    }

    /**
     * @param InvalidJsonException $ex
     */
    private function assertJsonError(InvalidJsonException $ex)
    {
        $this->assertEquals(json_last_error(), $ex->getJsonError());
        $this->assertEquals(json_last_error_msg(), $ex->getJsonErrorMessage());
    }
}
