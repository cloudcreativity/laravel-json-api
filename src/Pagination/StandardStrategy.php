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

namespace CloudCreativity\LaravelJsonApi\Pagination;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PagingStrategyInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Http\Query\BaseQueryParserInterface;

/**
 * Class StandardStrategy
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class StandardStrategy implements PagingStrategyInterface
{

    use CreatesPages;

    /**
     * @var string|null
     */
    protected $pageKey;

    /**
     * @var string|null
     */
    protected $perPageKey;

    /**
     * @var array|null
     */
    protected $columns;

    /**
     * @var bool|null
     */
    protected $simplePagination;

    /**
     * @var bool|null
     */
    protected $underscoreMeta;

    /**
     * @var string|null
     */
    protected $metaKey;

    /**
     * @var string|null
     */
    protected $primaryKey;

    /**
     * StandardStrategy constructor.
     */
    public function __construct()
    {
        $this->metaKey = BaseQueryParserInterface::PARAM_PAGE;
    }

    /**
     * Set the qualified column name that is being used for the resource's ID.
     *
     * @param $keyName
     * @return $this
     */
    public function withQualifiedKeyName($keyName)
    {
        $this->primaryKey = $keyName;

        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function withPageKey($key)
    {
        $this->pageKey = $key;

        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function withPerPageKey($key)
    {
        $this->perPageKey = $key;

        return $this;
    }

    /**
     * @param $cols
     * @return $this;
     */
    public function withColumns($cols)
    {
        $this->columns = $cols;

        return $this;
    }

    /**
     * @return $this
     */
    public function withSimplePagination()
    {
        $this->simplePagination = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function withLengthAwarePagination()
    {
        $this->simplePagination = false;

        return $this;
    }

    /**
     * @return $this
     */
    public function withUnderscoredMetaKeys()
    {
        $this->underscoreMeta = true;

        return $this;
    }

    /**
     * Set the key for the paging meta.
     *
     * Use this to 'nest' the paging meta in a sub-key of the JSON API document's top-level meta object.
     * A string sets the key to use for nesting. Use `null` to indicate no nesting.
     *
     * @param string|null $key
     * @return $this
     */
    public function withMetaKey($key)
    {
        $this->metaKey = $key ?: null;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function paginate($query, QueryParametersInterface $parameters)
    {
        $pageParameters = collect((array) $parameters->getPaginationParameters());

        $paginator = $this
            ->defaultOrder($query)
            ->query($query, $pageParameters);

        return $this->createPage($paginator, $parameters);
    }

    /**
     * @param Collection $collection
     * @return int
     */
    protected function getPerPage(Collection $collection)
    {
        return (int) $collection->get($this->getPerPageKey());
    }

    /**
     * Get the default per-page value for the query.
     *
     * If the query is an Eloquent builder, we can pass in `null` as the default,
     * which then delegates to the model to get the default. Otherwise the Laravel
     * standard default is 15.
     *
     * @param $query
     * @return int|null
     */
    protected function getDefaultPerPage($query)
    {
        return $query instanceof EloquentBuilder ? null : 15;
    }

    /**
     * @return array
     */
    protected function getColumns()
    {
        return $this->columns ?: ['*'];
    }

    /**
     * @return bool
     */
    protected function isSimplePagination()
    {
        return (bool) $this->simplePagination;
    }

    /**
     * @param $query
     * @return bool
     */
    protected function willSimplePaginate($query)
    {
        return $this->isSimplePagination() && method_exists($query, 'simplePaginate');
    }

    /**
     * Apply a deterministic order to the page.
     *
     * @param QueryBuilder|EloquentBuilder|Relation $query
     * @return $this
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/313
     */
    protected function defaultOrder($query)
    {
        if ($this->doesRequireOrdering($query)) {
            $query->orderBy($this->primaryKey);
        }

        return $this;
    }

    /**
     * Do we need to apply a deterministic order to the query?
     *
     * If the primary key has not been used for a sort order already, we use it
     * to ensure the page has a deterministic order.
     *
     * @param QueryBuilder|EloquentBuilder|Relation $query
     * @return bool
     */
    protected function doesRequireOrdering($query)
    {
        if (!$this->primaryKey) {
            return false;
        }

        $query = ($query instanceof Relation) ? $query->getBaseQuery() : $query->getQuery();

        return !collect($query->orders ?: [])->contains(function (array $order) {
            $col = $order['column'] ?? '';
            return $this->primaryKey === $col;
        });
    }

    /**
     * @param QueryBuilder|EloquentBuilder|Relation $query
     * @param Collection $pagingParameters
     * @return mixed
     */
    protected function query($query, Collection $pagingParameters)
    {
        $pageName = $this->getPageKey();
        $size = $this->getPerPage($pagingParameters) ?: $this->getDefaultPerPage($query);
        $cols = $this->getColumns();

        return $this->willSimplePaginate($query) ?
            $query->simplePaginate($size, $cols, $pageName) :
            $query->paginate($size, $cols, $pageName);
    }

}
