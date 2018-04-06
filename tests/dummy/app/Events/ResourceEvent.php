<?php

namespace DummyApp\Events;

use CloudCreativity\LaravelJsonApi\Broadcasting\BroadcastsData;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Queue\SerializesModels;

class ResourceEvent implements ShouldBroadcast
{

    use SerializesModels, BroadcastsData;

    /**
     * @var string
     */
    public $hook;

    /**
     * @var object
     */
    public $record;

    /**
     * @var ResourceObjectInterface
     */
    public $resource;

    /**
     * ResourceEvent constructor.
     *
     * @param string $hook
     * @param object|null $record
     * @param ResourceObjectInterface|null $resource
     */
    public function __construct($hook, $record, ResourceObjectInterface $resource = null)
    {
        $this->hook = $hook;
        $this->record = $record;
        $this->resource = $resource;
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
        return is_object($this->record) ? $this->serializeData($this->record) : [];
    }
}
