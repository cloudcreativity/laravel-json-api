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

namespace DummyApp\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Schema\EloquentSchema;
use DummyApp\Post;

class Schema extends EloquentSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @var array
     */
    protected $attributes = [
        'title',
        'slug',
        'content',
        'published_at' => 'published',
    ];

    /**
     * @var array
     */
    protected $relationships = [
        'author',
        'comments',
        'tags',
    ];

    /**
     * @param Post $record
     * @param bool $isPrimary
     * @param array $includedRelationships
     * @return array
     */
    public function getRelationships($record, $isPrimary, array $includedRelationships)
    {
        $relationships = parent::getRelationships($record, $isPrimary, $includedRelationships);
        $relationships['comments'][self::META] = function () use ($record, $isPrimary) {
            return $isPrimary ? ['count' => $record->comments()->count()] : null;
        };

        return $relationships;
    }
}
