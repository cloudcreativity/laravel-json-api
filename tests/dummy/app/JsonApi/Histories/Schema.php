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

namespace DummyApp\JsonApi\Histories;

use CloudCreativity\LaravelJsonApi\Schema\SchemaProvider;
use DummyApp\History;

class Schema extends SchemaProvider
{
    /**
     * @var string
     */
    protected string $resourceType = 'histories';

    /**
     * @param History|object $resource
     * @return array
     */
    public function getAttributes(object $resource): array
    {
        return [
            'createdAt' => $resource->created_at,
            'detail' => $resource->detail,
            'updatedAt' => $resource->updated_at,
        ];
    }

    /**
     * @param History|object $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships(object $resource,  bool $isPrimary, array $includeRelationships): array
    {
        return [
            'user' => [
                self::SHOW_SELF => false,
                self::SHOW_RELATED => false,
                self::SHOW_DATA => isset($includeRelationships['user']),
                self::DATA => static function () use ($resource) {
                    return $resource->user;
                },
            ],
        ];
    }
}
