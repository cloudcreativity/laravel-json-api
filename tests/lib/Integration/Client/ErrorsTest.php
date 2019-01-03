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
