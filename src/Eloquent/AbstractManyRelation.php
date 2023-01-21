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

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\LaravelJsonApi\Adapter\AbstractRelationshipAdapter;
use CloudCreativity\LaravelJsonApi\Contracts\Adapter\HasManyAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use Illuminate\Database\Eloquent\Model;

/**
 * Class AbstractManyRelation
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractManyRelation extends AbstractRelationshipAdapter implements HasManyAdapterInterface
{

    use Concerns\QueriesRelations;

    /**
     * @var string
     */
    protected $key;

    /**
     * AbstractManyRelation constructor.
     *
     * @param string $key
     */
    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * @param Model $record
     * @param QueryParametersInterface $parameters
     * @return mixed
     */
    public function query($record, QueryParametersInterface $parameters)
    {
        $relation = $this->getRelation($record, $this->key);

        return $this->adapterFor($relation)->queryToMany($relation, $parameters);
    }

}
