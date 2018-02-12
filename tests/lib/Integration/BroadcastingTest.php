<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use DummyApp\Post;
use DummyApp\Events\PostCreated;

class BroadcastingTest extends TestCase
{

    public function testBroadcastWith()
    {
        $event = new PostCreated($post = factory(Post::class)->create());
        $data = $event->broadcastWith();

        $this->assertSame('posts', array_get($data, 'data.type'));
        $this->assertEquals($id = $post->getKey(), array_get($data, 'data.id'));
    }
}
