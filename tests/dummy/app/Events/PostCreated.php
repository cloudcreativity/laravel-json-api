<?php

namespace DummyApp\Events;

use CloudCreativity\LaravelJsonApi\Broadcasting\BroadcastsData;
use DummyApp\Post;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class PostCreated implements ShouldBroadcast
{

    use SerializesModels, BroadcastsData;

    /**
     * @var Post
     */
    public $post;

    /**
     * PostCreated constructor.
     *
     * @param Post $post
     */
    public function __construct(Post $post)
    {
        $this->post = $post;
    }

    /**
     * @return array
     */
    public function broadcastOn()
    {
        return [new Channel('public')];
    }

    /**
     * @return array
     */
    public function broadcastWith()
    {
        return $this->serializeData($this->post);
    }
}
