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

use Countable;
use Illuminate\Database\Eloquent\Collection;
use IteratorAggregate;

/**
 * Class CursorPaginator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class CursorPaginator implements IteratorAggregate, Countable
{

    /**
     * @var Collection
     */
    private $items;

    /**
     * @var bool
     */
    private $more;

    /**
     * @var Cursor
     */
    private $cursor;

    /**
     * @var string
     */
    private $key;

    /**
     * CursorPaginator constructor.
     *
     * @param mixed $items
     * @param bool $more
     *      whether there are more items.
     * @param Cursor $cursor
     * @param string $key
     *      the key used for the after/before identifiers.
     */
    public function __construct($items, $more, Cursor $cursor, $key)
    {
        $this->more = $more;
        $this->items = collect($items);
        $this->cursor = $cursor;
        $this->key = $key;
    }

    /**
     * @return Collection
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * @return mixed|null
     */
    public function firstItem()
    {
        if ($first = $this->items->first()) {
            return $first->{$this->key};
        }

        return null;
    }

    /**
     * @return mixed|null
     */
    public function lastItem()
    {
        if ($last = $this->items->last()) {
            return $last->{$this->key};
        }

        return null;
    }

    /**
     * @return bool
     */
    public function hasMore()
    {
        return $this->more;
    }

    /**
     * @return bool
     */
    public function hasNoMore()
    {
        return !$this->hasMore();
    }

    /**
     * @return int
     */
    public function getPerPage()
    {
        return $this->cursor->getLimit();
    }

    /**
     * @return string|null
     */
    public function getFrom(): ?string
    {
        $first = $this->firstItem();

        return $first ? (string) $first : null;
    }

    /**
     * @return string|null
     */
    public function getTo(): ?string
    {
        $last = $this->lastItem();

        return $last ? (string) $last : null;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): \Traversable
    {
        return $this->items->getIterator();
    }

    /**
     * @inheritDoc
     */
    public function count(): int
    {
        return $this->items->count();
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->items->isEmpty();
    }

    /**
     * @return bool
     */
    public function isNotEmpty(): bool
    {
        return !$this->isEmpty();
    }

}
