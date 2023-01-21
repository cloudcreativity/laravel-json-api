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

namespace CloudCreativity\LaravelJsonApi\Adapter;

use CloudCreativity\LaravelJsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\LaravelJsonApi\Store\StoreAwareTrait;

/**
 * Class AbstractRelationshipAdapter
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractRelationshipAdapter implements RelationshipAdapterInterface, StoreAwareInterface
{

    use StoreAwareTrait;

    /**
     * The JSON API field name of the relation.
     *
     * @var string|null
     */
    protected $field;

    /**
     * @inheritdoc
     */
    public function withFieldName($name)
    {
        $this->field = $name;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function relationship($record, QueryParametersInterface $parameters)
    {
        return $this->query($record, $parameters);
    }

    /**
     * Find the related record for a to-one relationship.
     *
     * @param array $relationship
     * @return mixed|null
     */
    protected function findToOne(array $relationship)
    {
        return $this->getStore()->findToOne($relationship);
    }

    /**
     * @param array $relationship
     * @return array
     */
    protected function findToMany(array $relationship)
    {
        return $this->getStore()->findToMany($relationship);
    }

}
