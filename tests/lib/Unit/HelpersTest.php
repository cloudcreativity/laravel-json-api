<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Unit;

use CloudCreativity\JsonApi\Exceptions\InvalidJsonException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use function CloudCreativity\JsonApi\http_contains_body;
use function CloudCreativity\JsonApi\json_decode;

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
        try {
            json_decode($content);
            $this->fail('No exception thrown.');
        } catch (InvalidJsonException $ex) {
            if ($jsonError) {
                $this->assertJsonError($ex);
            }
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
     * @param InvalidJsonException $ex
     */
    private function assertJsonError(InvalidJsonException $ex)
    {
        $this->assertEquals(json_last_error(), $ex->getJsonError());
        $this->assertEquals(json_last_error_msg(), $ex->getJsonErrorMessage());
    }
}
