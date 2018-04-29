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
use CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest;
use DummyApp\Events\ResourceEvent;
use DummyApp\Post;

class PostsController extends JsonApiController
{

    /**
     * @param ValidatedRequest $request
     */
    public function searching(ValidatedRequest $request)
    {
        event(new ResourceEvent('searching', null, $request));
    }

    /**
     * @param Post $record
     * @param ValidatedRequest $request
     */
    public function reading(Post $record, ValidatedRequest $request)
    {
        event(new ResourceEvent('reading', $record, $request));
    }

    /**
     * @param $record
     * @param ValidatedRequest $request
     */
    public function saving($record, ValidatedRequest $request)
    {
        event(new ResourceEvent('saving', $record, $request));
    }

    /**
     * @param ValidatedRequest $request
     */
    public function creating(ValidatedRequest $request)
    {
        event(new ResourceEvent('creating', null, $request));
    }

    /**
     * @param Post $record
     * @param ValidatedRequest $request
     */
    public function updating(Post $record, ValidatedRequest $request)
    {
        event(new ResourceEvent('updating', $record, $request));
    }

    /**
     * @param Post $record
     * @param ValidatedRequest $request
     */
    public function saved(Post $record, ValidatedRequest $request)
    {
        event(new ResourceEvent('saved', $record, $request));
    }

    /**
     * @param Post $record
     * @param ValidatedRequest $request
     */
    public function created(Post $record, ValidatedRequest $request)
    {
        event(new ResourceEvent('created', $record, $request));
    }

    /**
     * @param Post $record
     * @param ValidatedRequest $request
     */
    public function updated(Post $record, ValidatedRequest $request)
    {
        event(new ResourceEvent('updated', $record, $request));
    }

    /**
     * @param Post $record
     * @param ValidatedRequest $request
     */
    public function deleting(Post $record, ValidatedRequest $request)
    {
        event(new ResourceEvent('deleting', $record, $request));
    }

    /**
     * @param Post $record
     * @param ValidatedRequest $request
     */
    public function deleted(Post $record, ValidatedRequest $request)
    {
        event(new ResourceEvent('deleted', $record->getKey(), $request));
    }

}
