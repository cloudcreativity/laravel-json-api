<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidJsonException;
use CloudCreativity\LaravelJsonApi\Exceptions\NotFoundException;
use DummyApp\Post;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\Exceptions\MaintenanceModeException;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorsTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->doNotRethrowExceptions();
    }

    /**
     * Returns a JSON API error for 404.
     */
    public function test404()
    {
        $this->doRead('999')->assertStatus(404)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Not Found',
                    'status' => '404',
                ],
            ],
        ]);
    }

    /**
     * Can override the default 404 error message.
     */
    public function testCustom404()
    {
        $expected = $this->withCustomError(NotFoundException::class);

        $this->doRead('999')->assertStatus(404)->assertExactJson($expected);
    }

    /**
     * @return array
     */
    public function invalidDocumentProvider()
    {
        return [
            'empty' => [''],
            'array' => ['[]'],
            'bool' => ['true'],
            'string' => ['"foo"'],
            'number' => ['1'],
            'null' => ['null'],
            'PATCH' => ['[]', 'PATCH'],
        ];
    }

    /**
     * Returns a JSON API error when a document is not provided, or is not an object.
     *
     * @param string $content
     * @param string $method
     * @dataProvider invalidDocumentProvider
     */
    public function testDocumentRequired($content, $method = 'POST')
    {
        if ('POST' === $method) {
            $uri = $this->apiUrl()->getResourceTypeUrl('posts');
        } else {
            $model = factory(Post::class)->create();
            $uri = $this->apiUrl()->getResourceUrl('posts', $model);
        }

        $expected = [
            'errors' => [
                [
                    'title' => 'Document Required',
                    'status' => '400',
                    'detail' => 'Expecting request to contain a JSON API document.',
                ],
            ],
        ];

        $this->doInvalidRequest($uri, $content, $method)
            ->assertStatus(400)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    /**
     * @return array
     */
    public function ignoreDocumentProvider()
    {
        return [
            'empty' => [''],
            'array' => ['[]'],
            'bool' => ['true'],
            'string' => ['"foo"'],
            'number' => ['1'],
            'null' => ['null'],
            'DELETE' => ['[]', 'DELETE'],
        ];
    }

    /**
     * @param $content
     * @param $method
     * @dataProvider ignoreDocumentProvider
     */
    public function testIgnoresData($content, $method = 'GET')
    {
        $model = factory(Post::class)->create();
        $uri = $this->api()->url()->update('posts', $model);

        $this->doInvalidRequest($uri, $content, $method)
            ->assertSuccessful();
    }

    /**
     * Can override the default document required error.
     */
    public function testCustomDocumentRequired()
    {
        $uri = $this->apiUrl()->getResourceTypeUrl('posts');
        $expected = $this->withCustomError(DocumentRequiredException::class);

        $this->doInvalidRequest($uri, '')
            ->assertStatus(400)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    /**
     * Returns a JSON API error when the submitted JSON is invalid.
     */
    public function testInvalidJson()
    {
        $uri = $this->api()->url()->create('posts');
        $content = '{"data": {}';

        $this->doInvalidRequest($uri, $content)->assertStatus(400)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Invalid JSON',
                    'code' => 4,
                    'status' => '400',
                    'detail' => 'Syntax error',
                ],
            ],
        ]);
    }

    /**
     * Can override the invalid JSON error.
     */
    public function testCustomInvalidJson()
    {
        $uri = $this->api()->url()->create('posts');
        $expected = $this->withCustomError(InvalidJsonException::class);
        $content = '{"data": {}';

        $this->doInvalidRequest($uri, $content)->assertStatus(400)->assertExactJson($expected);
    }

    /**
     * If the client sends a request wanting JSON API (i.e. a JSON API Accept header),
     * whatever error is generated by the application must be returned as a JSON API error
     * even if the error has not been generated from one of the configured APIs.
     */
    public function testClientWantsJsonApiError()
    {
        $expected = [
            'errors' => [
                [
                    'title' => 'Not Found',
                    'status' => '404',
                ],
            ],
        ];

        $this->postJsonApi('/api/v99/posts')
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson($expected);
    }

    public function testMaintenanceMode()
    {
        $ex = new MaintenanceModeException(Carbon::now()->getTimestamp(), 60, "We'll be back soon.");

        $this->request($ex)
            ->assertStatus(503)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson([
                'errors' => [
                    [
                        'title' => 'Service Unavailable',
                        'detail' => "We'll be back soon.",
                        'status' => '503',
                    ],
                ],
            ]);
    }

    /**
     * By default Laravel sends a 419 response for a TokenMismatchException.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/181
     */
    public function testTokenMismatch()
    {
        $ex = new TokenMismatchException("The token is not valid.");

        $this->request($ex)
            ->assertStatus(419)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson([
                'errors' => [
                    [
                        'title' => 'Invalid Token',
                        'detail' => 'The token is not valid.',
                        'status' => '419',
                    ],
                ],
            ]);
    }

    /**
     * If we get a Laravel validation exception we need to convert this to
     * JSON API errors.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/182
     */
    public function testValidationException()
    {
        $messages = new MessageBag([
            'email' => $detail = 'These credentials do not match our records.',
        ]);

        $validator = $this->createMock(Validator::class);
        $validator->method('getMessageBag')->willReturn($messages);

        $ex = new ValidationException($validator);

        $this->request($ex)
            ->assertStatus(422)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson([
                'errors' => [
                    [
                        'title' => 'Unprocessable Entity',
                        'status' => '422',
                        'detail' => $detail,
                        'meta' => ['key' => 'email'],
                    ],
                ],
            ]);
    }

    public function testHttpException()
    {
        $ex = new HttpException(
            418,
            "I think I might be a teapot.",
            null,
            ['X-Teapot' => 'True']
        );

        $this->request($ex)
            ->assertStatus(418)
            ->assertHeader('X-Teapot', 'True')
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson([
                'errors' => [
                    [
                        'title' => "I'm a teapot",
                        'detail' => 'I think I might be a teapot.',
                        'status' => '418',
                    ]
                ],
            ]);
    }

    public function testGenericException()
    {
        $ex = new \Exception('Boom.');

        $this->request($ex)
            ->assertStatus(500)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertExactJson([
                'errors' => [
                    [
                        'title' => 'Internal Server Error',
                        'status' => '500',
                    ],
                ],
            ]);
    }

    /**
     * @param \Exception $ex
     * @return \CloudCreativity\LaravelJsonApi\Testing\TestResponse
     */
    private function request(\Exception $ex)
    {
        Route::get('/test', function () use ($ex) {
            throw $ex;
        });

        return $this->getJsonApi('/test');
    }

    /**
     * @param $key
     * @return array
     */
    private function withCustomError($key)
    {
        config()->set("json-api-v1.errors.{$key}", $expected = [
            'title' => 'Foo',
            'detail' => 'Bar',
        ]);

        return ['errors' => [$expected]];
    }

    /**
     * @param $uri
     * @param $content
     * @param $method
     * @return \Illuminate\Foundation\Testing\TestResponse
     */
    private function doInvalidRequest($uri, $content, $method = 'POST')
    {
        $headers = $this->transformHeadersToServerVars([
            'CONTENT_LENGTH' => mb_strlen($content, '8bit'),
            'CONTENT_TYPE' => 'application/vnd.api+json',
            'Accept' => 'application/vnd.api+json',
        ]);

        return $this->call($method, $uri, [], [], [], $headers, $content);
    }
}
