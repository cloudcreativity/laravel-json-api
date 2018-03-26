<?php

namespace CloudCreativity\LaravelJsonApi\Tests\Integration;

use DummyApp\Events\ResourceEvent;
use DummyApp\Post;

class BroadcastingTest extends TestCase
{

    public function testBroadcastWith()
    {
        $post = factory(Post::class)->create();
        $event = new ResourceEvent('created', $post);
        $data = $event->broadcastWith();

        $this->assertSame('posts', array_get($data, 'data.type'));
        $this->assertEquals($id = $post->getKey(), array_get($data, 'data.id'));
    }
}
