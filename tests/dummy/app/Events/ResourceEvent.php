<?php
/**
 * Copyright 2018 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
