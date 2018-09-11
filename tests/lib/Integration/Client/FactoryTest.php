<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Client;

use DummyApp\Post;
use GuzzleHttp\Client;

class FactoryTest extends TestCase
{

    /**
     * Uses the host in the JSON API config file.
     */
    public function testWithoutHost()
    {
        $client = $this->api()->client(['handler' => $this->handler]);
        $post = factory(Post::class)->make();

        $this->willSeeResource($post, 201);
        $client->create($post);

        $this->assertSame(
            'http://localhost/api/v1/posts',
            (string) $this->mock->getLastRequest()->getUri()
        );
    }

    /**
     * The host that is provided can have a path as well.
     */
    public function testWithHostAndPath()
    {
        $client = $this->api()->client(
            'http://example.com/foo',
            ['handler' => $this->handler]
        );

        $post = factory(Post::class)->make();

        $this->willSeeResource($post, 201);
        $client->create($post);

        $this->assertSame(
            'http://example.com/foo/api/v1/posts',
            (string) $this->mock->getLastRequest()->getUri()
        );
    }

    /**
     * A Guzzle client can be provided.
     */
    public function testWithGuzzleClient()
    {
        $guzzle = new Client([
            'handler' => $this->handler,
            'base_uri' => 'http://foobar.local/baz/bat/',
        ]);

        $client = $this->api()->client($guzzle);
        $post = factory(Post::class)->make();

        $this->willSeeResource($post, 201);
        $client->create($post);

        $this->assertSame(
            'http://foobar.local/baz/bat/posts',
            (string) $this->mock->getLastRequest()->getUri()
        );
    }

}
