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

use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidJsonException;
use CloudCreativity\LaravelJsonApi\Exceptions\NotFoundException;

class ErrorsTest extends TestCase
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

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
     * Returns a JSON API error when a document is not provided.
     */
    public function testDocumentRequired()
    {
        $uri = $this->api()->url()->create('posts');

        $this->postJsonApi($uri, '')->assertStatus(400)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Document Required',
                    'status' => '400',
                    'detail' => 'Expecting request to contain a JSON API document.',
                ],
            ],
        ]);
    }

    /**
     * Can override the default document required error.
     */
    public function testCustomDocumentRequired()
    {
        $uri = $this->api()->url()->create('posts');
        $expected = $this->withCustomError(DocumentRequiredException::class);

        $this->postJsonApi($uri, '')->assertStatus(400)->assertExactJson($expected);
    }

    /**
     * Returns a JSON API error when the submitted JSON is invalid.
     */
    public function testInvalidJson()
    {
        $uri = $this->api()->url()->create('posts');
        $content = '{"data": {}';

        $this->postJsonApi($uri, $content)->assertStatus(400)->assertExactJson([
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

        $this->postJsonApi($uri, $content)->assertStatus(400)->assertExactJson($expected);
    }

    /**
     * @param $key
     * @return array
     */
    private function withCustomError($key)
    {
        config()->set("json-api-default.errors.{$key}", $expected = [
            'title' => 'Foo',
            'detail' => 'Bar',
        ]);

        return ['errors' => [$expected]];
    }
}