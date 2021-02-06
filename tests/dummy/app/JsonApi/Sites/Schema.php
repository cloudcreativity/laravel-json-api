<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace DummyApp\JsonApi\Sites;

use DummyApp\Entities\Site;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'sites';

    /**
     * @var array
     */
    protected $attributes = [
        'domain',
        'name',
    ];

    /**
     * @param Site $resource
     * @return string
     */
    public function getId($resource)
    {
        return $resource->getSlug();
    }

    /**
     * @param Site $resource
     * @return array
     */
    public function getAttributes($resource)
    {
        return [
            'domain' => $resource->getDomain(),
            'name' => $resource->getName(),
        ];
    }

}

