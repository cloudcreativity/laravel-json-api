<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Exceptions\NotFoundException;
use DummyApp\Post;

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
        $post = factory(Post::class)->create();
        $uri = $this->api()->url()->update('posts', $post);

        $this->patchJsonApi($uri, [])->assertStatus(400)->assertExactJson([
            'errors' => [
                [
                    'title' => 'Document Required',
                    'status' => '400',
                    'detail' => 'Expecting request to contain a JSON API document.',
                ],
            ],
        ]);
    }

    public function testCustomDocumentRequired()
    {
        $post = factory(Post::class)->create();
        $uri = $this->api()->url()->update('posts', $post);
        $expected = $this->withCustomError(DocumentRequiredException::class);

        $this->patchJsonApi($uri, [])->assertStatus(400)->assertExactJson($expected);
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