<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Search;

use Illuminate\Database\Eloquent\Builder;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\SortParameterInterface;

/**
 * Class AbstractSortedSearch
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractSortedSearch extends AbstractSearch
{

    /**
     * @var array
     */
    protected $sortColumns = [];

    /**
     * @param Builder $builder
     * @param array $sortBy
     */
    protected function sort(Builder $builder, array $sortBy)
    {
        /** @var SortParameterInterface $param */
        foreach ($sortBy as $param) {
            $this->sortBy($builder, $param);
        }
    }

    /**
     * @param Builder $builder
     * @param SortParameterInterface $param
     */
    protected function sortBy(Builder $builder, SortParameterInterface $param)
    {
        $column = $this->getQualifiedSortColumn($builder, $param->getField());
        $order = $param->isAscending() ? 'asc' : 'desc';

        $builder->orderBy($column, $order);
    }

    /**
     * @param Builder $builder
     * @param string $field
     * @return string
     */
    protected function getQualifiedSortColumn(Builder $builder, $field)
    {
        $key = $this->columnForField($field);

        if (!str_contains('.', $key)) {
            $key = sprintf('%s.%s', $builder->getModel()->getTable(), $key);
        }

        return $key;
    }

    /**
     * Get the table column to use for the specified search field.
     *
     * @param string $field
     * @return string
     */
    protected function columnForField($field)
    {
        return isset($this->sortColumns[$field]) ? $this->sortColumns[$field] : $field;
    }
}
