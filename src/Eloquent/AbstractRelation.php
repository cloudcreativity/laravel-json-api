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

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\JsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\JsonApi\Store\StoreAwareTrait;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AbstractRelation
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractRelation implements RelationshipAdapterInterface, StoreAwareInterface
{

    use StoreAwareTrait;

    /**
     * @var Model
     */
    protected $model;

    /**
     * The model key.
     *
     * @var string
     */
    protected $key;

    /**
     * @var string|null
     */
    protected $relationshipName;

    /**
     * AbstractRelation constructor.
     *
     * @param Model $model
     * @param $key
     * @param null $relationshipName
     */
    public function __construct(Model $model, $key, $relationshipName = null)
    {
        $this->model = $model;
        $this->key = $key;
        $this->relationshipName = $relationshipName;
    }

    /**
     * Set the relationship name.
     *
     * @param $name
     * @return $this
     */
    public function withRelationshipName($name)
    {
        $this->relationshipName = $name;

        return $this;
    }

}
