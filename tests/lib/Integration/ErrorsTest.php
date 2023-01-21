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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Document\Error\Error;
use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidJsonException;
use CloudCreativity\LaravelJsonApi\Exceptions\JsonApiException;
use CloudCreativity\LaravelJsonApi\Exceptions\ResourceNotFoundException;
use DummyApp\Post;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\Exceptions\MaintenanceModeException;
use Illuminate\Http\Response;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException;
use LaravelJsonApi\Testing\TestResponse;
use Neomerx\JsonApi\Document\Error as NeomerxError;
use Neomerx\JsonApi\Exceptions\JsonApiException as NeomerxException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ErrorsTest extends TestCase
{

    /**
     * Returns a JSON API error for 404.
     */
    public function test404()
    {
        $response = $this
            ->jsonApi()
            ->get('/api/v1/posts/999');

        $response->assertStatus(404)->assertErrorStatus([
            'title' => 'Not Found',
            'status' => '404',
        ]);
    }

    /**
     * Can override the default 404 error message.
     */
    public function testCustom404()
    {
        $this->markTestSkipped('@todo work out how to override translation config');
        $expected = $this->withCustomError(ResourceNotFoundException::class);

        $response = $this
            ->jsonApi()
            ->get('/api/v1/posts/999');

        $response->assertStatus(404)->assertExactJson($expected);
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
            $uri = '/api/v1/posts';
        } else {
            $uri = url('/api/v1/posts', factory(Post::class)->create());
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
        $uri = url('/api/v1/posts', $model);

        $this->doInvalidRequest($uri, $content, $method)
            ->assertSuccessful();
    }

    /**
     * Can override the default document required error.
     */
    public function testCustomDocumentRequired()
    {
        $this->markTestSkipped('@todo work out how to override translation config');
        $uri = '/api/v1/posts';
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
        $uri = '/api/v1/posts';
        $content = '{"data": {}';

        $this->doInvalidRequest($uri, $content)->assertStatus(400)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Invalid JSON',
                    'code' => '4',
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
        $this->markTestSkipped('@todo work out how to override translation config');
        $uri = '/api/v1/posts';
        $expected = $this->withCustomError(InvalidJsonException::class);
        $content = '{"data": {}';

        $this->doInvalidRequest($uri, $content)->assertStatus(400)->assertExactJson($expected);
    }

    /**
     * If the client sends a request wanting JSON API (i.e. a JSON API Accept header),
     * whatever error is generated by the application must be returned as a JSON API error
     * even if the error has not been generated from one of the configured APIs.
     */
    public function testClientWantsJsonApiError(): void
    {
        $expected = [
            'errors' => [
                [
                    'title' => 'Not Found',
                    'status' => '404',
                ],
            ],
        ];

        $response = $this
            ->jsonApi()
            ->post('/api/v99/posts');

        $response
            ->assertStatus(404)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertJson($expected);
    }

    /**
     * If content negotiation has occurred on a JSON API route, then we always expect
     * a JSON API response if the matched codec supports encoding to JSON API.
     *
     * This can occur when the client has said it accepts any media type: the JSON API
     * media type will match, then any subsequent exceptions should be rendered as JSON
     * API.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/329
     */
    public function testAcceptAny()
    {
        $expected = [
            'status' => '400',
            'source' => [
                'parameter' => 'include',
            ],
        ];

        $response = $this->jsonApi()->accept('*/*')->includePaths('foo')->get('/api/v1/posts');
        $response->assertErrorStatus($expected);
    }

    /**
     * If we get a JSON API exception outside of a JSON API route, but have not
     * asked for a JSON API response, then it must be converted to a HTTP exception.
     * Otherwise it will always render as a 500 Internal Server error.
     *
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/329
     */
    public function testNeomerxJsonApiException()
    {
        config()->set('app.debug', true);

        Route::get('/test', function () {
            throw new NeomerxException(new NeomerxError(
                null,
                null,
                422,
                null,
                null,
                'My foobar error message.'
            ), 418);
        });

        $this->get('/test', ['Accept' => '*/*'])
            ->assertStatus(418)
            ->assertSee('My foobar error message.');
    }

    public function testJsonApiException1(): void
    {
        Route::get('/test', function () {
            throw JsonApiException::make(Error::fromArray([
                'status' => '418',
                'detail' => "Hello, I'm a teapot.",
            ]))->withHeaders(['X-Foo' => 'Bar']);
        });

        $expected = [
            'errors' => [
                [
                    'status' => '418',
                    'detail' => "Hello, I'm a teapot.",
                ],
            ],
        ];

        $this->get('/test')
            ->assertStatus(418)
            ->assertHeader('Content-Type', 'application/vnd.api+json')
            ->assertHeader('X-Foo', 'Bar')
            ->assertExactJson($expected);
    }

    /**
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/566
     */
    public function testJsonApiException2(): void
    {
        Route::get('/test', function () {
            $error = Error::fromArray([
                'title' => 'The language you want to use is not active',
                'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
            ]);

            throw new JsonApiException($error);
        });

        $expected = [
            'errors' => [
                [
                    'status' => '422',
                    'title' => 'The language you want to use is not active',
                ],
            ],
        ];

        $this->get('/test')
            ->assertStatus(422)
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

        $expected = [
            'title' => 'Invalid Token',
            'detail' => 'The token is not valid.',
            'status' => '419',
        ];

        $this->request($ex)
            ->assertErrorStatus($expected)
            ->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    /**
     * The Symfony bad request exception does not implement the HTTP exception
     * interface, so we need to ensure we handle it.
     */
    public function testBadRequestException(): void
    {
        $ex = new BadRequestException('The request format is bad.');

        $expected = [
            'title' => 'Bad Request',
            'detail' => 'The request format is bad.',
            'status' => '400',
        ];

        $this->request($ex)
            ->assertExactErrorStatus($expected)
            ->assertHeader('Content-Type', 'application/vnd.api+json');
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
        $validator->method('errors')->willReturn($messages);

        $ex = new ValidationException($validator);

        $this->request($ex)->assertStatus(422)->assertExactErrorStatus([
            'title' => 'Unprocessable Entity',
            'status' => '422',
            'detail' => $detail,
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
     * @return TestResponse
     */
    private function request(\Exception $ex)
    {
        Route::get('/test', function () use ($ex) {
            throw $ex;
        });

        return $this->jsonApi()->get('/test');
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
     * @return \Illuminate\Testing\TestResponse
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
