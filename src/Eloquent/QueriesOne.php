<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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
use CloudCreativity\LaravelJsonApi\Eloquent\Concerns\QueriesRelations;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Builder;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class QueriesOne
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class QueriesOne extends AbstractRelationshipAdapter
{

    use QueriesRelations;

    /**
     * @var \Closure
     */
    private $factory;

    /**
     * QueriesOne constructor.
     *
     * @param \Closure $factory
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
    public function query($record, EncodingParametersInterface $parameters)
    {
        $relation = $this($record);

        return $this->adapterFor($relation)->queryToOne($relation, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function relationship($record, EncodingParametersInterface $parameters)
    {
        return $this->query($record, $parameters);
    }

    /**
     * @inheritDoc
     */
    public function update($record, array $relationship, EncodingParametersInterface $parameters)
    {
        throw new RuntimeException('Modifying a queries-one relation is not supported.');
    }

    /**
     * @inheritDoc
     */
    public function replace($record, array $relationship, EncodingParametersInterface $parameters)
    {
        throw new RuntimeException('Modifying a queries-one relation is not supported.');
    }

}
