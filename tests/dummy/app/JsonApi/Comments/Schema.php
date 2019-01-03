<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace DummyApp\JsonApi\Comments;

use CloudCreativity\LaravelJsonApi\Schema\EloquentSchema;
use DummyApp\Comment;

class Schema extends EloquentSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'comments';

    /**
     * @var array
     */
    protected $attributes = [
        'content'
    ];

    /**
     * @param Comment $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     * @todo have left this in so that deprecated methods are tested.
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'commentable' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::DATA => isset($includeRelationships['commentable']) ?
                    $resource->commentable : $this->createBelongsToIdentity($resource, 'commentable'),
            ],
            'created-by' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::DATA => isset($includeRelationships['created-by']) ?
                    $resource->user : $this->createBelongsToIdentity($resource, 'user'),
            ],
        ];
    }
}
