<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Http\Controllers;

use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\JsonApi\Contracts\Hydrator\HydratorInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;

/**
 * Class EloquentController
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class EloquentController extends JsonApiController
{

    use CreatesResponses;

    /**
     * Map of URI relationship names to model relationship keys.
     *
     * By default the URI relationship name will be camel-cased to get the model
     * relationship name. You can override this default for a particular relationship
     * by entering a mapping in this array as a key/value pair - URI relationship
     * name as the key, model relationship name as the value.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * @var Model
     */
    private $model;

    /**
     * EloquentController constructor.
     *
     * @param Model $model
     * @param HydratorInterface|string|null $hydrator
     */
    public function __construct(Model $model, $hydrator = null)
    {
        $this->model = $model;
        $this->hydrator = $hydrator;
    }

    /**
     * @param Model $record
     * @return bool
     */
    protected function destroyRecord($record)
    {
        return $record->delete();
    }

    /**
     * Convert a relationship name into the attribute name to get the relationship from the model.
     *
     * @param $relationshipName
     *      the relationship name as it appears in the uri.
     * @return string
     *      the key to use on the model.
     */
    protected function keyForRelationship($relationshipName)
    {
        return isset($this->relationships[$relationshipName]) ?
            $this->relationships[$relationshipName] : Str::camel($relationshipName);
    }

    /**
     * @param RequestInterface $request
     * @return Model
     */
    protected function getRecord(RequestInterface $request)
    {
        $record = $request->getRecord();

        if (!$record instanceof Model) {
            throw new RuntimeException(sprintf('%s expects to be used with a %s record.', static::class, Model::class));
        }

        return $record;
    }
}
