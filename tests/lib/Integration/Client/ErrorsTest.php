<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use CloudCreativity\LaravelJsonApi\Exceptions\ClientException;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\TransferException;

class ErrorsTest extends TestCase
{

    public function testWithoutErrorObjects()
    {
        $this->willSeeErrors([], 422);

        try {
            $this->client->read('posts', '1');
            $this->fail('No exception thrown.');
        } catch (ClientException $ex) {
            $this->assertSame(422, $ex->getHttpCode());
            $this->assertEmpty($ex->getErrors());
            $this->assertInstanceOf(BadResponseException::class, $ex->getPrevious());
        }
    }

    public function testWithErrorObjects()
    {
        $this->willSeeErrors([
            'errors' => $expected = [
                [
                    'id' => "536d04b6-3a76-43ed-8c2f-9e60e6e68aa1",
                    'links' => [
                        'about' => 'http://localhost/errors/server',
                    ],
                    'status' => '500',
                    'code' => 'foobar',
                    'title' => 'Server Error',
                    'detail' => 'An unexpected error occurred.',
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
            $this->client->query('posts');
            $this->fail('No exception thrown.');
        } catch (ClientException $ex) {
            $this->assertEquals(collect($expected), $ex->getErrors());
        }
    }

    public function testTransferException()
    {
        $expected = new TransferException();

        $this->mock->append($expected);

        try {
            $this->client->read('posts', '1');
            $this->fail('No exception thrown.');
        } catch (ClientException $ex) {
            $this->assertNull($ex->getResponse());
            $this->assertNull($ex->getHttpCode());
            $this->assertSame($expected, $ex->getPrevious());
        }
    }
}
