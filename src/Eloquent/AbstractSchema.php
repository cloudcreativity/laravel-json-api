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

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Schema\CreatesEloquentIdentities;
use CloudCreativity\LaravelJsonApi\Schema\CreatesLinks;
use Illuminate\Database\Eloquent\Model;
use Neomerx\JsonApi\Schema\SchemaProvider;

/**
 * Class EloquentSchema
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractSchema extends SchemaProvider
{

    use CreatesLinks,
        CreatesEloquentIdentities,
        Concerns\SerializesAttributes;

    /**
     * The API this schema relates to.
     *
     * If this is not set, then the API handling the HTTP request is the default. If you are
     * encoding JSON API resources outside of a HTTP request - e.g. queued broadcasting -
     * you must specify the API that the schema belongs to if using the `links()` helper
     * method.
     *
     * @var string|null
     */
    protected $api;

    /**
     * The attribute to use for the resource id.
     *
     * If null, defaults to `Model::getKeyName()`
     *
     * @var
     */
    protected $idName;

    /**
     * @param object $resource
     * @return mixed
     */
    public function getId($resource)
    {
        if (!$resource instanceof Model) {
            throw new RuntimeException('Expecting an Eloquent model.');
        }

        $key = $this->idName ?: $resource->getKeyName();

        return (string) $resource->{$key};
    }

    /**
     * @param object $resource
     * @return array
     */
    public function getAttributes($resource)
    {
        if (!$resource instanceof Model) {
            throw new RuntimeException('Expecting an Eloquent model to serialize.');
        }

        return array_merge(
            $this->getDefaultAttributes($resource),
            $this->getModelAttributes($resource)
        );
    }

}
