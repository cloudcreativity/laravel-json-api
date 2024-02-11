<?php
/*
 * Copyright 2024 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Schema\DashCaseRelationUrls;
use CloudCreativity\LaravelJsonApi\Schema\SchemaProvider;
use DummyApp\Comment;

class Schema extends SchemaProvider
{
    use DashCaseRelationUrls;

    /**
     * @var string
     */
    protected string $resourceType = 'comments';

    /**
     * @param Comment|object $resource
     * @return array
     */
    public function getAttributes(object $resource): array
    {
        return [
            'createdAt' => $resource->created_at,
            'content' => $resource->content,
            'updatedAt' => $resource->updated_at,
        ];
    }

    /**
     * @param Comment|object $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'commentable' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['commentable']),
                self::DATA => function () use ($resource) {
                    return $resource->commentable;
                },
            ],
            'createdBy' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['createdBy']),
                self::DATA => function () use ($resource) {
                    return $resource->user;
                },
            ],
        ];
    }
}
