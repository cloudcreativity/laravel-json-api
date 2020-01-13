<?php
/**
 * Copyright 2020 Cloud Creativity Limited
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

use DummyApp\Post;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * @param Post $resource
     * @return string
     */
    public function getId($resource)
    {
        return (string) $resource->getRouteKey();
    }

    /**
     * @param Post $resource
     * @return array
     */
    public function getAttributes($resource)
    {
        return [
            /** There are some client tests that use an unsaved post. */
            'created-at' => $resource->created_at ? $resource->created_at->toAtomString() : null,
            'content' => $resource->content,
            'deleted-at' => $resource->deleted_at ? $resource->deleted_at->toAtomString() : null,
            'published' => $resource->published_at ? $resource->published_at->toAtomString() : null,
            'slug' => $resource->slug,
            'title' => $resource->title,
            'updated-at' => $resource->updated_at ? $resource->updated_at->toAtomString() : null,
        ];
    }

    /**
     * @param Post $record
     * @param bool $isPrimary
     * @param array $includedRelationships
     * @return array
     */
    public function getRelationships($record, $isPrimary, array $includedRelationships)
    {
        return [
            'author' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includedRelationships['author']),
                self::DATA => function () use ($record) {
                    return $record->author;
                },
            ],
            'comments' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includedRelationships['comments']),
                self::DATA => function () use ($record) {
                    return $record->comments;
                },
                self::META => function () use ($record, $isPrimary) {
                    return $isPrimary ? ['count' => $record->comments()->count()] : null;
                },
            ],
            'tags' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includedRelationships['tags']),
                self::DATA => function () use ($record) {
                    return $record->tags;
                },
            ],
        ];
    }
}
