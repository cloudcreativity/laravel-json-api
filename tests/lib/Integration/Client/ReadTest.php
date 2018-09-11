<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use DummyApp\Post;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class ReadTest extends TestCase
{

    /**
     * @var Post
     */
    private $post;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();
        $this->post = factory(Post::class)->create();
    }

    public function test()
    {
        $expected = $this->willSeeResource($this->post);
        $response = $this->client->read('posts', $this->post->getRouteKey());

        $this->assertSame($expected, $response->getPsrResponse());
        $this->assertNotNull($response->getDocument());
        $this->assertRequested('GET', "/posts/{$this->post->getRouteKey()}");
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testWithParameters()
    {
        $parameters = new EncodingParameters(
            ['author', 'site'],
            ['author' => ['first-name', 'surname'], 'site' => ['uri']]
        );

        $this->willSeeResource($this->post);
        $this->client->read('posts', '1', $parameters);

        $this->assertQueryParameters([
            'include' => 'author,site',
            'fields[author]' => 'first-name,surname',
            'fields[site]' => 'uri',
        ]);
    }

    public function testWithOptions()
    {
        $this->willSeeResource($this->post);
        $this->client->read('posts', '1', null, [
            'headers' => [
                'X-Foo' => 'Bar',
            ],
        ]);

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testError()
    {
        $this->willSeeErrors([], 404);
        $this->expectException(JsonApiException::class);
        $this->client->read('posts', '1');
    }
}
