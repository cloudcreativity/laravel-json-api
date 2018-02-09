<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;

class ContentNegotiationTest extends TestCase
{

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->withDefaultApi([], function (ApiGroup $api) {
            $api->resource('posts');
        });
    }

    public function testOkWithoutBody()
    {
        $this->getJsonApi('/api/v1/posts')
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/vnd.api+json');
    }

    public function testOkWithBody()
    {
        $data = $this->willPatch();

        $this->patchJsonApi("/api/v1/posts/{$data['id']}", ['data' => $data])->assertStatus(200);
    }

    public function testNotOkWithoutBody()
    {
        $data = $this->willPatch();

        $headers = $this->transformHeadersToServerVars(['Accept' => 'application/vnd.api+json']);
        $this->call('PATCH', "/api/v1/posts/{$data['id']}", [], [], [], $headers)->assertStatus(400);
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

        $this->patchJson("/api/v1/posts/{$data['id']}", ['data' => $data], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'text/plain',
        ])->assertStatus(415);
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
        $this->get('/api/v1/posts', ['Accept' => 'application/json'])->assertStatus(406);
    }

    /**
     * The codec configuration can be changed.
     */
    public function testCanChangeMediaType1()
    {
        app('config')->set('json-api-default.codecs', [
            'encoders' => ['application/json'],
            'decoders' => ['application/json'],
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
        app('config')->set('json-api-default.codecs', [
            'encoders' => ['application/json'],
            'decoders' => ['application/json'],
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
