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

namespace DummyApp\JsonApi\Tags;

use CloudCreativity\LaravelJsonApi\Schema\SchemaProvider;
use DummyApp\Tag;

class Schema extends SchemaProvider
{
    /**
     * @var string
     */
    protected string $resourceType = 'tags';

    /**
     * @param Tag|object $resource
     * @return array
     */
    public function getAttributes(object $resource): array
    {
        return [
            'createdAt' => $resource->created_at,
            'updatedAt' => $resource->updated_at,
            'name' => $resource->name,
        ];
    }
}
