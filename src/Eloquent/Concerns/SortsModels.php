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

namespace CloudCreativity\LaravelJsonApi\Eloquent\Concerns;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\SortParameterInterface;
use CloudCreativity\LaravelJsonApi\Http\Query\SortParameter;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait SortsModels
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait SortsModels
{

    /**
     * The default sorting to use if no sort parameters have been provided.
     *
     * Used when the client does not provide any sort parameters. Use
     * either a string for a single default sort field, or an array
     * of strings for multiple default sort fields.
     *
     * As per the JSON API convention, sort parameters prefixed with
     * a `-` denoted descending order.
     *
     * For example, if `name` ascending is the default:
     *
     * ```
     * protected $defaultSort = 'name';
     * ```
     *
     * Or if `created-at` descending, then `name` ascending is the default:
     *
     * ```
     * protected $defaultSort = ['-created-at`, 'name'];
     * ```
     *
     * @var string|string[]
     */
    protected $defaultSort = [];

    /**
     * A mapping of sort parameters to columns.
     *
     * Use this to map any parameters to columns where the two are not identical. E.g. if
     * your sort param is called `category` but the column to use is `type`, then set this
     * property to `['category' => 'type']`.
     *
     * @var array
     */
    protected $sortColumns = [];

    /**
     * Apply sort parameters to the query.
     *
     * @param Builder $query
     * @param SortParameterInterface[] $sortBy
     * @return void
     */
    protected function sort($query, array $sortBy)
    {
        /** @var SortParameterInterface $param */
        foreach ($sortBy as $param) {
            $this->sortBy($query, $param);
        }
    }

    /**
     * Get sort parameters to use when the client has not provided a sort order.
     *
     * @return array
     */
    protected function defaultSort()
    {
        return collect($this->defaultSort)->map(function ($param) {
            $desc = ($param[0] === '-');
            $field = ltrim($param, '-');

            return new SortParameter($field, !$desc);
        })->all();
    }

    /**
     * @param Builder $query
     * @param SortParameterInterface $param
     * @return void
     */
    protected function sortBy($query, SortParameterInterface $param)
    {
        $direction = $param->isAscending() ? 'asc' : 'desc';
        $method = 'sortBy' . Str::classify($param->getField());

        if (method_exists($this, $method)) {
            $this->{$method}($query, $direction);
            return;
        }

        $column = $this->getQualifiedSortColumn($query, $param->getField());

        $query->orderBy($column, $direction);
    }

    /**
     * @param Builder $query
     * @param string $field
     * @return string
     */
    protected function getQualifiedSortColumn($query, $field)
    {
        $key = $this->getSortColumn($field, $query->getModel());

        return $query->getModel()->qualifyColumn($key);
    }

    /**
     * Get the table column to use for the specified search field.
     *
     * @param string $field
     * @param Model $model
     * @return string
     */
    protected function getSortColumn($field, Model $model)
    {
        /** If there is a custom mapping, return that */
        if (isset($this->sortColumns[$field])) {
            return $this->sortColumns[$field];
        }

        return $model::$snakeAttributes ? Str::underscore($field) : Str::camelize($field);
    }

}
