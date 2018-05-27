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

namespace DummyApp\JsonApi\Sites;

use CloudCreativity\LaravelJsonApi\Schema\ExtractsAttributesTrait;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use DummyApp\Entities\Site;
use InvalidArgumentException;
use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

    use ExtractsAttributesTrait;

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
     * @param object $resource
     * @return mixed
     */
    public function getId($resource)
    {
        if (!$resource instanceof Site) {
            throw new InvalidArgumentException('Expecting a site object.');
        }

        return $resource->getSlug();
    }

    /**
     * @param $record
     * @param $recordKey
     * @return mixed
     */
    protected function extractAttribute($record, $recordKey)
    {
        $method = 'get' . Str::classify($recordKey);

        return call_user_func([$record, $method]);
    }

}

