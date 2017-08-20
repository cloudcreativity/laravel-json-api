<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use CloudCreativity\LaravelJsonApi\Tests\Models\Post;
use CloudCreativity\LaravelJsonApi\Tests\Events\PostCreated;

class BroadcastingTest extends TestCase
{

    public function testBroadcastWith()
    {
        $event = new PostCreated($post = factory(Post::class)->create());
        $data = $event->broadcastWith();

        $this->assertSame('posts', array_get($data, 'data.type'));
        $this->assertEquals($id = $post->getKey(), array_get($data, 'data.id'));
        $this->assertEquals("http://localhost/api/v1/posts/$id", array_get($data, 'data.links.self'));
    }
}
