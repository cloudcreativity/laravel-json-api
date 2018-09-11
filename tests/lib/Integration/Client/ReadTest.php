<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use CloudCreativity\LaravelJsonApi\Exceptions\ClientException;
use DummyApp\Post;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

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

        $this->assertSame($expected, $response);
        $this->assertRequested('GET', "/posts/{$this->post->getRouteKey()}");
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testWithObject()
    {
        $expected = $this->willSeeResource($this->post);
        $response = $this->client->read($this->post);

        $this->assertSame($expected, $response);
        $this->assertRequested('GET', "/posts/{$this->post->getRouteKey()}");
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
        $options = [
            'headers' => [
                'X-Foo' => 'Bar',
            ],
        ];

        $this->willSeeResource($this->post);
        $this->client->withOptions($options)->read('posts', '1');

        $this->assertHeader('X-Foo', 'Bar');
        $this->assertHeader('Accept', 'application/vnd.api+json');
    }

    public function testError()
    {
        $this->willSeeErrors([], 404);
        $this->expectException(ClientException::class);
        $this->client->read('posts', '1');
    }
}
