<?php
/*
 * Copyright 2023 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Integration\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Broadcasting\BroadcastsData;
use CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;

class TestEvent
{

    use SerializesModels, BroadcastsData;

    /**
     * @var string
     */
    public $hook;

    /**
     * @var mixed|null
     */
    public $record;

    /**
     * @var ValidatedRequest|null
     */
    public $request;

    /**
     * @var mixed|null
     */
    public $related;

    /**
     * ResourceEvent constructor.
     *
     * @param string $hook
     * @param mixed|null $record
     * @param ValidatedRequest|null $request
     * @param mixed|null $related
     */
    public function __construct(
        $hook,
        $record,
        ValidatedRequest $request = null,
        $related = null
    ) {
        $this->hook = $hook;
        $this->record = $record;
        $this->request = $request;
        $this->related = $related;
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
