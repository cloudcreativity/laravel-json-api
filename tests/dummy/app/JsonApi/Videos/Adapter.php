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

namespace DummyApp\JsonApi\Videos;

use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Store\EloquentAdapter;
use DummyApp\Video;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class Adapter extends EloquentAdapter
{


    /**
     * Adapter constructor.
     */
    public function __construct()
    {
        parent::__construct(new Video());
    }

    /**
     * @param ResourceObjectInterface $resource
     * @return Video
     */
    protected function createRecord(ResourceObjectInterface $resource)
    {
        $video = new Video();
        $video->{$video->getKeyName()} = $resource->getId();
        $video->user()->associate(Auth::user());

        return $video;
    }

    /**
     * @inheritDoc
     */
    protected function filter($query, Collection $filters)
    {
        // TODO: Implement filter() method.
    }

}
