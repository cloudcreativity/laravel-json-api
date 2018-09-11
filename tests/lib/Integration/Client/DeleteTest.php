<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use CloudCreativity\LaravelJsonApi\Exceptions\ClientException;
use DummyApp\Post;

class DeleteTest extends TestCase
{

    /**
     * @var Post
     */
    private $post;

    /**
     *
     */
    protected function setUp()
    {
        parent::setUp();
        $this->post = factory(Post::class)->create();
    }

    public function test()
    {
        $expected = $this->willSeeResponse(null, 204);
        $response = $this->client->delete($this->post);
        $this->assertSame($expected, $response);
        $this->assertRequested('DELETE', "/posts/{$this->post->getRouteKey()}");
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testWithOptions()
    {
        $this->willSeeResponse(null, 204);
        $this->client->delete($this->post, [
            'headers' => [
                'X-Foo' => 'Bar',
            ],
        ]);

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testErrors()
    {
        $this->willSeeErrors([], 405);
        $this->expectException(ClientException::class);
        $this->client->delete($this->post);
    }
}
