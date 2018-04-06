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

use CloudCreativity\LaravelJsonApi\Schema\EloquentSchema;
use DummyApp\Video;

class Schema extends EloquentSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'videos';

    /**
     * @var array|null
     */
    protected $attributes = null;

    /**
     * @param Video $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'uploaded-by' => [
                self::DATA => isset($includeRelationships['uploaded-by']) ?
                    $resource->user : $this->createBelongsToIdentity($resource, 'user'),
            ],
        ];
    }
}

