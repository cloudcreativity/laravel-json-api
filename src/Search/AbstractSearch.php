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

use CloudCreativity\JsonApi\Contracts\Http\HttpServiceInterface;
use CloudCreativity\JsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\JsonApi\Contracts\Pagination\PaginatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Search\SearchInterface;
use CloudCreativity\LaravelJsonApi\Pagination\Page;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\SortParameterInterface;

/**
 * Class EloquentFilter
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractSearch implements SearchInterface
{

    /**
     * The default per page amount.
     *
     * @var int
     */
    protected $perPage = 15;

    /**
     * The maximum resources allowed per page, or no maximum.
     *
     * If a maximum is specified, the result set will always be paginated even
     * if the client did not send any paging parameters. If you want the client
     * to always have the choice, leave this as zero.
     *
     * @var int
     *      the limit, or zero for no limit.
     */
    protected $maxPerPage = 0;

    /**
     * Whether simple pagination should be used instead of length aware pagination.
     *
     * @var bool
     */
    protected $simplePagination = false;

    /**
     * A mapping of sort parameters to columns.
     *
     * Use this to map any parameters to columns where the two are not identical. E.g. if
     * your sort param is called `sort` but the column to use is `type`, then set this
     * property to `['sort' => 'type']`.
     *
     * @var array
     */
    protected $sortColumns = [];

    /**
     * The filter param for a find-many request.
     *
     * @var string
     */
    protected $findManyFilter = DocumentInterface::KEYWORD_ID;

    /**
     * Apply the supplied filters to the builder instance.
     *
     * @param Builder $builder
     * @param Collection $filters
     * @return void
     */
    abstract protected function filter(Builder $builder, Collection $filters);

    /**
     * Is this a search for a singleton resource?
     *
     * @param Collection $filters
     * @return bool
     */
    abstract protected function isSearchOne(Collection $filters);

    /**
     * @var HttpServiceInterface
     */
    private $service;

    /**
     * @var PaginatorInterface
     */
    private $paginator;

    /**
     * AbstractSearch constructor.
     * @param HttpServiceInterface $service
     * @param PaginatorInterface $paginator
     */
    public function __construct(HttpServiceInterface $service, PaginatorInterface $paginator)
    {
        $this->service = $service;
        $this->paginator = $paginator;
    }

    /**
     * @inheritdoc
     */
    public function search(Builder $builder, EncodingParametersInterface $parameters)
    {
        $filters = new Collection((array) $parameters->getFilteringParameters());

        if ($this->isFindMany($filters)) {
            return $this->findMany($builder, $filters);
        }

        $this->filter($builder, $filters);
        $this->sort($builder, (array) $parameters->getSortParameters());

        if ($this->isSearchOne($filters)) {
            return $this->first($builder);
        }

        return $this->isPaginated() ? $this->paginate($builder) : $this->all($builder);
    }

    /**
     * Apply sort parameters to the query.
     *
     * @param Builder $query
     * @param SortParameterInterface[] $sortBy
     * @return void
     */
    protected function sort(Builder $query, array $sortBy)
    {
        if (empty($sortBy)) {
            $this->defaultSort($query);
            return;
        }

        /** @var SortParameterInterface $param */
        foreach ($sortBy as $param) {
            $this->sortBy($query, $param);
        }
    }

    /**
     * @param Collection $filters
     * @return bool
     */
    protected function isFindMany(Collection $filters)
    {
        return $filters->has($this->getFindManyKey());
    }

    /**
     * @return string
     */
    protected function getFindManyKey()
    {
        return $this->findManyFilter;
    }

    /**
     * Should the result set be paginated?
     *
     * @return bool
     */
    protected function isPaginated()
    {
        return $this->isAlwaysPaginated() || is_int($this->paginator->getCurrentPage());
    }

    /**
     * @return bool
     */
    protected function isAlwaysPaginated()
    {
        return 0 < $this->maxPerPage;
    }

    /**
     * @return int
     */
    protected function getPerPage()
    {
        return $this->paginator->getPerPage($this->perPage, $this->maxPerPage ?: null);
    }

    /**
     * @param Builder $builder
     * @return PageInterface
     */
    protected function paginate(Builder $builder)
    {
        $size = $this->getPerPage();
        $page = new Page($this->service);

        $data = $this->simplePagination ? $builder->simplePaginate($size) : $builder->paginate($size);
        $page->setData($data);

        return $page;
    }

    /**
     * @param Builder $builder
     * @return EloquentCollection
     */
    protected function all(Builder $builder)
    {
        return $builder->get();
    }

    /**
     * @param Builder $builder
     * @return Model
     */
    protected function first(Builder $builder)
    {
        return $builder->first();
    }

    /**
     * @param Builder $builder
     * @param Collection $filters
     * @return EloquentCollection
     */
    protected function findMany(Builder $builder, Collection $filters)
    {
        $ids = $filters->get($this->getFindManyKey());

        return $builder->findMany($this->normalizeIds($ids));
    }

    /**
     * @param $ids
     * @return array
     */
    protected function normalizeIds($ids)
    {
        return is_array($ids) ? $ids : explode(',', (string) $ids);
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
        $key = $this->columnForField($field, $builder->getModel());

        if (!str_contains('.', $key)) {
            $key = sprintf('%s.%s', $builder->getModel()->getTable(), $key);
        }

        return $key;
    }

    /**
     * Get the table column to use for the specified search field.
     *
     * @param string $field
     * @param Model $model
     * @return string
     */
    protected function columnForField($field, Model $model)
    {
        /** If there is a custom mapping, return that */
        if (isset($this->sortColumns[$field])) {
            return $this->sortColumns[$field];
        }

        return $model::$snakeAttributes ? Str::snake($field) : Str::camel($field);
    }

    /**
     * Apply a default sort order if the client has not requested any sort order.
     *
     * Child classes can override this method if they want to implement their
     * own default sort order.
     *
     * @param Builder $builder
     * @return void
     */
    protected function defaultSort(Builder $builder)
    {
    }

}
