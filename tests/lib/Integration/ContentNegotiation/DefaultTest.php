<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\ContentNegotiation;

use CloudCreativity\LaravelJsonApi\Tests\Integration\TestCase;
use DummyApp\Post;

class DefaultTest extends TestCase
{

    public function testOkWithoutBody()
    {
        $this->getJsonApi('/api/v1/posts')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    public function testOkWithBody()
    {
        $data = $this->willPatch();

        $this->patchJsonApi("/api/v1/posts/{$data['id']}", [], ['data' => $data])->assertStatus(200);
    }

    public function testNotOkWithoutBody()
    {
        $data = $this->willPatch();

        $headers = $this->transformHeadersToServerVars(['Accept' => 'application/vnd.api+json']);
        $response = $this->call('PATCH', "/api/v1/posts/{$data['id']}", [], [], [], $headers);

        $response->assertStatus(400)->assertExactJson([
            'errors' => [
                [
                    "status" => "400",
                    "title" => "Document Required",
                    "detail" => "Expecting request to contain a JSON API document.",
                ],
            ],
        ]);
    }

    /**
     * Have observed browsers sending a "Content-Length" header with an empty string on GET
     * requests. If no content is expected, this should be interpreted as not having
     * any content.
     */
    public function testEmptyContentLengthHeader()
    {
        $headers = $this->transformHeadersToServerVars(['Content-Length' => '']);
        $this->call('GET', "/api/v1/posts", [], [], [], $headers)->assertStatus(200);
    }

    /**
     * @see Issue #66
     */
    public function testDeleteWithoutBody()
    {
        $post = factory(Post::class)->create();
        $response = $this->delete("/api/v1/posts/{$post->getKey()}");
        $response->assertStatus(204);
    }

    public function testUnsupportedMediaType()
    {
        $data = $this->willPatch();
        $uri = "/api/v1/posts/{$data['id']}";

        $response = $this->jsonApi()->contentType('text/plain')->data($data)->patch($uri);

        $response->assertErrorStatus([
            'title' => 'Unsupported Media Type',
            'status' => '415',
            'detail' => 'The request entity has a media type which the server or resource does not support.',
        ]);
    }

    /**
     * Can request an alternative media-type that is in our configuration.
     * Note that the Symfony response automatically appends the charset to the
     * content-type header if it starts with `text/`.
     */
    public function testAcceptable()
    {
        $this->get('/api/v1/posts', ['Accept' => 'text/plain'])
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    }

    /**
     * If we request a content type that is not in our codec configuration, we
     * expect a 406 response.
     */
    public function testNotAcceptable()
    {
        $expected = ['message' =>
            "The requested resource is capable of generating only content not acceptable "
            . "according to the Accept headers sent in the request."
        ];

        $this->get('/api/v1/posts', ['Accept' => 'application/json'])
            ->assertStatus(406)
            ->assertExactJson($expected);
    }

    /**
     * The codec configuration can be changed.
     */
    public function testCanChangeMediaType1()
    {
        app('config')->set('json-api-v1.encoding', [
            'application/json',
        ]);

        $this->get('/api/v1/posts', ['Accept' => 'application/json'])
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/json');
    }

    /**
     * Not including the JSON API media type in our configuration results in a 406 response
     */
    public function testCanChangeMediaType2()
    {
        app('config')->set('json-api-v1.encoding', [
            'application/json',
        ]);

        $this->get('/api/v1/posts', ['Accept' => 'application/vnd.api+json'])
            ->assertStatus(406);
    }

    /**
     * @return array
     */
    private function willPatch()
    {
        $post = factory(Post::class)->create();

        return [
            'type' => 'posts',
            'id' => (string) $post->getKey(),
            'attributes' => [
                'title' => 'Hello World',
            ],
        ];
    }
}
