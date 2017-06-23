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

        $this->withDefaultApi(function (ApiGroup $api) {
            $api->resource('posts');
        });
    }

    public function testOkWithoutBody()
    {
        $this->get('/posts', [
            'Accept' => 'application/vnd.api+json',
        ])->assertStatus(200);
    }

    public function testOkWithBody()
    {
        $data = $this->willPatch();

        $this->patchJson("/posts/{$data['id']}", ['data' => $data], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'application/vnd.api+json',
        ])->assertStatus(200);
    }

    public function testNotOkWithoutBody()
    {
        $data = $this->willPatch();

        $headers = $this->transformHeadersToServerVars(['Accept' => 'application/vnd.api+json']);
        $this->call('PATCH', "/posts/{$data['id']}", [], [], [], $headers)->assertStatus(400);
    }

    /**
     * @see Issue #66
     */
    public function testDeleteWithoutBody()
    {
        $post = factory(Post::class)->create();
        $response = $this->delete("/posts/{$post->getKey()}");
        $response->assertStatus(204);
    }

    public function testUnsupportedMediaType()
    {
        $data = $this->willPatch();

        $this->patchJson("/posts/{$data['id']}", ['data' => $data], [
            'Accept' => 'application/vnd.api+json',
            'Content-Type' => 'text/plain',
        ])->assertStatus(415);
    }

    public function testNotAcceptable()
    {
        $this->get('/posts', ['Accept' => 'text/html'])->assertStatus(406);
    }

    /**
     * @return array
     */
    private function willPatch()
    {
        $post = factory(Post::class)->create();

        return [
            'type' => 'posts',
            'id' => $post->getKey(),
            'attributes' => [
                'title' => 'Hello World',
            ],
        ];
    }
}
