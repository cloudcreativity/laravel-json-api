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

namespace DummyPackage\Resources\Blogs;

use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'blogs';

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
            'article' => $resource->article,
            'createdAt' => $resource->created_at->toJSON(),
            'publishedAt' => $resource->published_at ? $resource->published_at->toJSON() : null,
            'title' => $resource->title,
            'updatedAt' => $resource->updated_at->toJSON(),
        ];
    }


}
