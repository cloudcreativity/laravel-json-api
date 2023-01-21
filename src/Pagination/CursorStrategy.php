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
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Utils\Arr;
use Neomerx\JsonApi\Contracts\Http\Query\BaseQueryParserInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;

/**
 * Class CursorStrategy
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class CursorStrategy implements PagingStrategyInterface
{

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var string
     */
    private $before = 'before';

    /**
     * @var string
     */
    private $after = 'after';

    /**
     * @var string
     */
    private $limit = 'limit';

    /**
     * @var string|null
     */
    private $meta = BaseQueryParserInterface::PARAM_PAGE;

    /**
     * @var bool
     */
    private $underscoredMeta = false;

    /**
     * @var bool
     */
    private $descending = true;

    /**
     * @var string|null
     */
    private $column;

    /**
     * @var string|null
     */
    private $identifier;

    /**
     * @var mixed|null
     */
    private $columns;

    /**
     * CursorStrategy constructor.
     *
     * @param Factory $factory
     */
    public function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param $key
     * @return $this
     */
    public function withAfterKey($key)
    {
        $this->after = $key;

        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function withBeforeKey($key)
    {
        $this->before = $key;

        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function withLimitKey($key)
    {
        $this->limit = $key;

        return $this;
    }

    /**
     * @param $key
     * @return $this
     */
    public function withMetaKey($key)
    {
        $this->meta = $key;

        return $this;
    }

    /**
     * @return $this
     */
    public function withUnderscoredMetaKeys()
    {
        $this->underscoredMeta = true;

        return $this;
    }

    /**
     * @return $this
     */
    public function withAscending()
    {
        $this->descending = false;

        return $this;
    }

    /**
     * Set the cursor column.
     *
     * @param $column
     * @return $this
     * @todo 2.0 pass qualified columns to the cursor builder.
     */
    public function withQualifiedColumn($column)
    {
        $parts = explode('.', $column);

        if (!isset($parts[1])) {
            throw new \InvalidArgumentException('Expecting a valid qualified column name.');
        }

        $this->withColumn($parts[1]);

        return $this;
    }

    /**
     * Set the cursor column.
     *
     * @param $column
     * @return $this
     * @deprecated 2.0 use `withQualifiedColumn` instead.
     */
    public function withColumn($column)
    {
        $this->column = $column;

        return $this;
    }

    /**
     * Set the column name for the resource's ID.
     *
     * @param string $keyName
     * @return $this
     * @todo 2.0 pass qualified key name to the cursor builder.
     */
    public function withQualifiedKeyName($keyName)
    {
        $parts = explode('.', $keyName);

        if (!isset($parts[1])) {
            throw new \InvalidArgumentException('Expecting a valid qualified column name.');
        }

        $this->withIdentifierColumn($parts[1]);

        return $this;
    }

    /**
     * Set the column for the before/after identifiers.
     *
     * @param string|null $column
     * @return $this
     * @deprecated 2.0 use `withQualifiedKeyName` instead.
     */
    public function withIdentifierColumn($column)
    {
        $this->identifier = $column;

        return $this;
    }

    /**
     * Set the select columns for the query.
     *
     * @param $cols
     * @return $this
     */
    public function withColumns($cols)
    {
        $this->columns = $cols;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function paginate($query, QueryParametersInterface $parameters)
    {
        $paginator = $this->query($query)->paginate(
            $this->cursor($parameters),
            $this->columns ?: ['*']
        );

        $parameters = $this->buildParams($parameters);

        return $this->factory->createPage(
            $paginator->getItems(),
            $this->createFirstLink($paginator, $parameters),
            $this->createPrevLink($paginator, $parameters),
            $this->createNextLink($paginator, $parameters),
            null,
            $this->createMeta($paginator),
            $this->meta
        );
    }

    /**
     * Create a new cursor query.
     *
     * @param $query
     * @return CursorBuilder
     */
    protected function query($query)
    {
        return new CursorBuilder(
            $query,
            $this->column,
            $this->identifier,
            $this->descending
        );
    }

    /**
     * Extract the cursor from the provided paging parameters.
     *
     * @param QueryParametersInterface $parameters
     * @return Cursor
     */
    protected function cursor(QueryParametersInterface $parameters)
    {
        return Cursor::create(
            (array) $parameters->getPaginationParameters(),
            $this->before,
            $this->after,
            $this->limit
        );
    }

    /**
     * @param CursorPaginator $paginator
     * @param array $parameters
     * @return LinkInterface
     */
    protected function createFirstLink(CursorPaginator $paginator, array $parameters = [])
    {
        return $this->createLink([
            $this->limit => $paginator->getPerPage(),
        ], $parameters);
    }

    /**
     * @param CursorPaginator $paginator
     * @param array $parameters
     * @return LinkInterface|null
     */
    protected function createNextLink(CursorPaginator $paginator, array $parameters = [])
    {
        if ($paginator->hasNoMore()) {
            return null;
        }

        return $this->createLink([
            $this->after => $paginator->lastItem(),
            $this->limit => $paginator->getPerPage(),
        ], $parameters);
    }

    /**
     * @param CursorPaginator $paginator
     * @param array $parameters
     * @return LinkInterface|null
     */
    protected function createPrevLink(CursorPaginator $paginator, array $parameters = [])
    {
        if ($paginator->isEmpty()) {
            return null;
        }

        return $this->createLink([
            $this->before => $paginator->firstItem(),
            $this->limit => $paginator->getPerPage(),
        ], $parameters);
    }

    /**
     * @param array $page
     * @param array $parameters
     * @param array|object|null $meta
     * @return LinkInterface
     */
    protected function createLink(array $page, array $parameters = [], $meta = null)
    {
        $parameters[BaseQueryParserInterface::PARAM_PAGE] = $page;

        return json_api()->links()->current($meta, $parameters);
    }

    /**
     * Build parameters that are to be included with pagination links.
     *
     * @param QueryParametersInterface $parameters
     * @return array
     */
    protected function buildParams(QueryParametersInterface $parameters)
    {
        return array_filter([
            BaseQueryParserInterface::PARAM_FILTER =>
                $parameters->getFilteringParameters(),
        ]);
    }

    /**
     * @param CursorPaginator $paginator
     * @return array
     */
    protected function createMeta(CursorPaginator $paginator)
    {
        $meta = [
            'per-page' => $paginator->getPerPage(),
            'from' => $paginator->getFrom(),
            'to' => $paginator->getTo(),
            'has-more' => $paginator->hasMore(),
        ];

        return $this->underscoredMeta ? Arr::underscore($meta) : $meta;
    }

}
