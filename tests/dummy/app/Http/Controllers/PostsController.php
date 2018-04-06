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

namespace DummyApp\Http\Controllers;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use DummyApp\Events\ResourceEvent;
use DummyApp\Post;

class PostsController extends JsonApiController
{

    /**
     * @param Post|null $record
     * @param $resource
     */
    public function saving($record, $resource)
    {
        event(new ResourceEvent('saving', $record, $resource));
    }

    /**
     * @param $resource
     */
    public function creating($resource)
    {
        event(new ResourceEvent('creating', null, $resource));
    }

    /**
     * @param Post $record
     * @param $resource
     */
    public function updating(Post $record, $resource)
    {
        event(new ResourceEvent('updating', $record, $resource));
    }

    /**
     * @param Post $record
     * @param $resource
     */
    public function saved(Post $record, $resource)
    {
        event(new ResourceEvent('saved', $record, $resource));
    }

    /**
     * @param Post $record
     * @param $resource
     */
    public function created(Post $record, $resource)
    {
        event(new ResourceEvent('created', $record, $resource));
    }

    /**
     * @param Post $record
     * @param $resource
     */
    public function updated(Post $record, $resource)
    {
        event(new ResourceEvent('updated', $record, $resource));
    }

    /**
     * @param Post $record
     */
    public function deleting(Post $record)
    {
        event(new ResourceEvent('deleting', $record));
    }

    /**
     * @param Post $record
     */
    public function deleted(Post $record)
    {
        event(new ResourceEvent('deleted', $record->getKey()));
    }

}
