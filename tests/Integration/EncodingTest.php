<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;
use CloudCreativity\LaravelJsonApi\Tests\Models\Post;

class EncodingTest extends TestCase
{

    /**
     * If the URL host is set to `null`, we expect the request host to be prepended to links.
     */
    public function testRequestedResourceHasRequestHost()
    {
        $id = factory(Post::class)->create()->getKey();
        config()->set('json-api-default.url.host', null);

        $json = $this
            ->withApiRoutes()
            ->getJsonApi("http://www.example.com/api/v1/posts/$id")
            ->json();

        $this->assertLinks('http://www.example.com', $id, $json);
    }

    /**
     * If the URL host is set to `false`, we do not expect a host to be prepended to links.
     */
    public function testRequestedResourceDoesNotHaveHost()
    {
        $id = factory(Post::class)->create()->getKey();
        config()->set('json-api-default.url.host', false);

        $json = $this
            ->withApiRoutes()
            ->getJsonApi("http://www.example.com/api/v1/posts/$id")
            ->json();

        $this->assertLinks('', $id, $json);
    }

    /**
     * When encoding outside of a request and the URL host is set to `null`, we expect the
     * `app.url` config setting to be used.
     */
    public function testSerializedResourceHasAppHost()
    {
        $post = factory(Post::class)->create();

        config()->set('app.url', $host = 'http://www.example.com');
        config()->set('json-api-default.url.host', null);

        $json = json_api('default')->encoder()->serializeData($post);
        $this->assertLinks($host, $post->getKey(), $json);
    }

    /**
     * When encoding outside of a request and the URL host is set to a specified host,
     * we expect that to be used.
     */
    public function testSerializedResourceHasSpecificHost()
    {
        $post = factory(Post::class)->create();

        config()->set('app.url', 'http://localhost');
        config()->set('json-api-default.url.host', $host = 'http://www.example.com');

        $json = json_api('default')->encoder()->serializeData($post);
        $this->assertLinks($host, $post->getKey(), $json);
    }

    /**
     * When encoding outside of a request and the URL host is set to `false`, we expect
     * no host in the serialized data.
     */
    public function testSerializedResourceDoesNotHaveAppHost()
    {
        $post = factory(Post::class)->create();

        config()->set('app.url', 'http://www.example.com');
        config()->set('json-api-default.url.host', false);

        $json = json_api('default')->encoder()->serializeData($post);
        $this->assertLinks('', $post->getKey(), $json);
    }

    /**
     * @param $host
     * @param $id
     * @param array $json
     */
    private function assertLinks($host, $id, array $json)
    {
        $this->assertArraySubset([
            'data' => [
                'links' => [
                    'self' => "$host/api/v1/posts/$id",
                ],
            ],
        ], $json);
    }

    /**
     * @return $this
     */
    private function withApiRoutes()
    {
        $this->withDefaultApi([], function (ApiGroup $api) {
            $api->resource('posts');
        });

        return $this;
    }
}
