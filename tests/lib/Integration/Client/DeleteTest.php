<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

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
        $this->assertSame($expected, $response->getPsrResponse());
        $this->assertNull($response->getDocument());
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
}
