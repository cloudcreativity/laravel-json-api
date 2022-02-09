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

use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;

/**
 * Class CursorBuilder
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class CursorBuilder
{

    /**
     * @var QueryBuilder|EloquentBuilder|Relation
     */
    private $query;

    /**
     * @var string
     */
    private $column;

    /**
     * @var string
     */
    private $key;

    /**
     * @var bool
     */
    private $descending;

    /**
     * CursorBuilder constructor.
     *
     * @param QueryBuilder|EloquentBuilder $query
     * @param string $column
     *      the column to use for the cursor.
     * @param string|null $key
     *      the key column that the before/after cursors related to.
     * @param bool $descending
     *      whether items are paged in descending order.
     */
    public function __construct($query, $column = null, $key = null, $descending = true)
    {
        if (!empty($query->orders)) {
            throw new RuntimeException('Cursor queries must not have an order applied.');
        }

        $this->query = $query;
        $this->column = $column ?: $this->guessColumn();
        $this->key = $key ?: $this->guessKey();
        $this->descending = $descending;
    }

    /**
     * @param Cursor $cursor
     * @param array $columns
     * @return CursorPaginator
     */
    public function paginate(Cursor $cursor, $columns = ['*'])
    {
        if ($cursor->isBefore()) {
            return $this->previous($cursor, $columns);
        }

        return $this->next($cursor, $columns);
    }

    /**
     * Get the next page.
     *
     * To calculate if there are more items in the list, we ask
     * for one more item than we actually need for the limit. We then
     * slice the results to remove this extra item.
     *
     * @param Cursor $cursor
     * @param $columns
     * @return CursorPaginator
     * @throws \OutOfRangeException
     *      if the cursor contains a before/after id that does not exist.
     */
    protected function next(Cursor $cursor, $columns)
    {
        if ($cursor->isAfter()) {
            $this->whereId($cursor->getAfter(), $this->descending ? '<' : '>');
        }

        $items = $this
            ->orderForNext()
            ->get($cursor->getLimit() + 1, $columns);

        $more = $items->count() > $cursor->getLimit();

        return new CursorPaginator(
            $items->slice(0, $cursor->getLimit()),
            $more,
            $cursor,
            $this->key
        );
    }

    /**
     * Get the previous page.
     *
     * To get the previous page, we need to sort in the opposite direction
     * (i.e. ascending rather than descending), then reverse the results
     * so that they are in the correct page order.
     *
     * The previous page always has-more items, because we know there is
     * at least one object ahead in the table - i.e. the one that was
     * provided as the before cursor.
     *
     * @param Cursor $cursor
     * @param $columns
     * @return CursorPaginator
     */
    protected function previous(Cursor $cursor, $columns)
    {
        $items = $this
            ->whereId($cursor->getBefore(), $this->descending ? '>' : '<')
            ->orderForPrevious()
            ->get($cursor->getLimit(), $columns)
            ->reverse()
            ->values();

        return new CursorPaginator($items, true, $cursor, $this->key);
    }

    /**
     * Add a where clause for the supplied id and operator.
     *
     * If we are paging on the key, then we only need one where clause - i.e.
     * on the key column.
     *
     * If we are paging on a column that is different than the key, we do not
     * assume that the column is unique. Therefore we add where clauses for
     * both the column plus then use the key column (which we expect to be
     * unique) to differentiate between any items that have the same value for
     * the non-unique column.
     *
     * @param $id
     * @param $operator
     * @return $this
     * @see https://stackoverflow.com/questions/38017054/mysql-cursor-based-pagination-with-multiple-columns
     */
    protected function whereId($id, $operator)
    {
        /** If we are paging on the key, we only need one where clause. */
        if ($this->isPagingOnKey()) {
            $this->query->where($this->key, $operator, $id);
            return $this;
        }

        $value = $this->getColumnValue($id);

        $this->query->where(
            $this->column, $operator . '=', $value
        )->where(function ($query) use ($id, $value, $operator) {
            /** @var QueryBuilder $query */
            $query->where($this->column, $operator, $value)->orWhere($this->key, $operator, $id);
        });

        return $this;
    }

    /**
     * Order items for a previous page query.
     *
     * A previous page query needs to retrieve items in the opposite
     * order from the desired order.
     *
     * @return $this
     */
    protected function orderForPrevious()
    {
        if ($this->descending) {
            $this->orderAsc();
        } else {
            $this->orderDesc();
        }

        return $this;
    }

    /**
     * Order items for a next page query.
     *
     * @return $this
     */
    protected function orderForNext()
    {
        if ($this->descending) {
            $this->orderDesc();
        } else {
            $this->orderAsc();
        }

        return $this;
    }

    /**
     * Order items in descending order.
     *
     * @return $this
     */
    protected function orderDesc()
    {
        $this->query->orderByDesc($this->column);

        if ($this->isNotPagingOnKey()) {
            $this->query->orderByDesc($this->key);
        }

        return $this;
    }

    /**
     * Order items in ascending order.
     *
     * @return $this
     */
    protected function orderAsc()
    {
        $this->query->orderBy($this->column);

        if ($this->isNotPagingOnKey()) {
            $this->query->orderBy($this->key);
        }

        return $this;
    }

    /**
     * @param $limit
     * @param mixed $columns
     * @return Collection
     */
    protected function get($limit, $columns)
    {
        return $this->query->take($limit)->get($columns);
    }

    /**
     * Get the column value for the provided id.
     *
     * @param $id
     * @return mixed
     * @throws \OutOfRangeException
     *      if the id does not exist.
     */
    protected function getColumnValue($id)
    {
        $value = $this
            ->getQuery() // we want the raw DB value, not the Model value as that can be mutated.
            ->where($this->key, $id)
            ->value($this->column);

        if (is_null($value)) {
            throw new \OutOfRangeException("Cursor key {$id} does not exist or has a null value.");
        }

        return $value;
    }

    /**
     * Get a base query builder instance.
     *
     * @return QueryBuilder
     */
    protected function getQuery()
    {
        if (!$this->query instanceof QueryBuilder) {
            return clone $this->query->getQuery();
        }

        return clone $this->query;
    }

    /**
     * Are we paging using the key column?
     *
     * @return bool
     */
    protected function isPagingOnKey()
    {
        return $this->column === $this->key;
    }

    /**
     * Are we not paging on the key column?
     *
     * @return bool
     */
    protected function isNotPagingOnKey()
    {
        return !$this->isPagingOnKey();
    }

    /**
     * Guess the column to use for the cursor.
     *
     * @return string
     */
    private function guessColumn()
    {
        if ($this->query instanceof EloquentBuilder || $this->query instanceof Relation) {
            return $this->query->getModel()->getCreatedAtColumn();
        }

        return Model::CREATED_AT;
    }

    /**
     * Guess the key to use for the cursor.
     *
     * @return string
     */
    private function guessKey()
    {
        if ($this->query instanceof EloquentBuilder || $this->query instanceof Relation) {
            return $this->query->getModel()->getRouteKeyName();
        }

        return 'id';
    }
}
