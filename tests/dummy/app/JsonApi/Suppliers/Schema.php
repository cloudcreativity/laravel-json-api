<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace DummyApp\JsonApi\Suppliers;

use CloudCreativity\LaravelJsonApi\Schema\DashCaseRelationUrls;
use CloudCreativity\LaravelJsonApi\Schema\SchemaProvider;
use DummyApp\Supplier;

class Schema extends SchemaProvider
{
    use DashCaseRelationUrls;

    /**
     * @var string
     */
    protected string $resourceType = 'suppliers';

    /**
     * @param Supplier|object $resource
     * @return array
     */
    public function getAttributes(object $resource): array
    {
        return ['name' => $resource->name];
    }

    /**
     * @param Supplier|object $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array
    {
        return [
            'userHistory' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['userHistory']),
                self::DATA => static function () use ($resource) {
                    return $resource->userHistory;
                },
            ],
        ];
    }
}
