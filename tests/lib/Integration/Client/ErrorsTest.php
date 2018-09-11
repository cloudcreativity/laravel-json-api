<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use CloudCreativity\LaravelJsonApi\Document\Error;
use GuzzleHttp\Exception\BadResponseException;
use Neomerx\JsonApi\Document\Link;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class ErrorsTest extends TestCase
{

    public function testWithoutErrorObjects()
    {
        $this->willSeeErrors([], 422);

        try {
            $this->client->read('posts', '1');
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertSame(422, $ex->getHttpCode());
            $this->assertEmpty($ex->getErrors()->getArrayCopy());
            $this->assertInstanceOf(BadResponseException::class, $ex->getPrevious());
        }
    }

    public function testWithErrorObjects()
    {
        $expected = new Error(
            "536d04b6-3a76-43ed-8c2f-9e60e6e68aa1",
            ['about' => new Link("http://localhost/errors/server", null, true)],
            500,
            "server",
            "Server Error",
            "An unexpected error occurred.",
            ["pointer" => "/"],
            ["foo" => "bar"]
        );

        $this->willSeeErrors([
            'errors' => [
                [
                    'id' => $expected->getId(),
                    'links' => [
                        'about' => 'http://localhost/errors/server',
                    ],
                    'status' => $expected->getStatus(),
                    'code' => $expected->getCode(),
                    'title' => $expected->getTitle(),
                    'detail' => $expected->getDetail(),
                    'source' => [
                        'pointer' => '/',
                    ],
                    'meta' => [
                        'foo' => 'bar',
                    ],
                ],
            ]
        ], 500);

        try {
            $this->client->index('posts');
            $this->fail('No exception thrown.');
        } catch (JsonApiException $ex) {
            $this->assertEquals([$expected], $ex->getErrors()->getArrayCopy());
        }
    }
}
