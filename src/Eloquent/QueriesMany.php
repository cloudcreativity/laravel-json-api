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
use CloudCreativity\LaravelJsonApi\Eloquent\Concerns\QueriesRelations;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Builder;

/**
 * Class QueriesMany
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class QueriesMany extends AbstractRelationshipAdapter implements HasManyAdapterInterface
{

    use QueriesRelations;

    /**
     * @var \Closure
     */
    private $factory;

    /**
     * QueriesMany constructor.
     */
    public function __construct(\Closure $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param $record
     * @return Builder
     */
    public function __invoke($record)
    {
        $fn = $this->factory;

        return $fn($record);
    }

    /**
     * @inheritDoc
     */
    public function query($record, QueryParametersInterface $parameters)
    {
        $relation = $this($record);

        return $this->adapterFor($relation)->queryToMany($relation, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function relationship($record, QueryParametersInterface $parameters)
    {
        return $this->query($record, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function update($record, array $relationship, QueryParametersInterface $parameters)
    {
        throw new RuntimeException('Modifying a queries-many relation is not supported.');
    }

    /**
     * @inheritDoc
     */
    public function replace($record, array $relationship, QueryParametersInterface $parameters)
    {
        throw new RuntimeException('Modifying a queries-many relation is not supported.');
    }

    /**
     * @inheritDoc
     */
    public function add($record, array $relationship, QueryParametersInterface $parameters)
    {
        throw new RuntimeException('Modifying a queries-many relation is not supported.');
    }

    /**
     * @inheritDoc
     */
    public function remove($record, array $relationship, QueryParametersInterface $parameters)
    {
        throw new RuntimeException('Modifying a queries-many relation is not supported.');
    }

}
