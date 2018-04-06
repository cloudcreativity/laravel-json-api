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

namespace DummyApp\JsonApi\Phones;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractSchema;
use DummyApp\Phone;

class Schema extends AbstractSchema
{

    /**
     * @var string
     */
    protected $resourceType = 'phones';

    /**
     * @var array
     */
    protected $attributes = [
        'number',
    ];

    /**
     * @param Phone $resource
     * @param bool $isPrimary
     * @param array $includeRelationships
     * @return array
     */
    public function getRelationships($resource, $isPrimary, array $includeRelationships)
    {
        return [
            'user' => [
                self::DATA => isset($includeRelationships['user']) ?
                    $resource->user : $this->createBelongsToIdentity($resource, 'user'),
            ],
        ];
    }
}
