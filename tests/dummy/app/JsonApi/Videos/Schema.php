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

namespace DummyApp\JsonApi\Videos;

use DummyApp\Video;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'videos';

    /**
     * @inheritDoc
     */
    public function getId($resource)
    {
        return (string) $resource->getRouteKey();
    }

    /**
     * @inheritDoc
     */
    public function getAttributes($resource)
    {
        return [
            'created-at' => $resource->created_at->toAtomString(),
            'description' => $resource->description,
            'title' => $resource->title,
            'updated-at' => $resource->updated_at->toAtomString(),
            'url' => $resource->url,
        ];
    }

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
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['uploaded-by']),
                self::DATA => function () use ($resource) {
                    return $resource->user;
                },
            ],
        ];
    }
}

