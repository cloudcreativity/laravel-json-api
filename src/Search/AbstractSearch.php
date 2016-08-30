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

use CloudCreativity\JsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\JsonApi\Contracts\Pagination\PaginatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Search\SearchInterface;
use CloudCreativity\LaravelJsonApi\Pagination\Page;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Contracts\Pagination\Paginator;
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
     * @param Builder $builder
     * @param SortParameterInterface[] $sortBy
     * @return void
     */
    abstract protected function sort(Builder $builder, array $sortBy);

    /**
     * Is this a search for a singleton resource?
     *
     * @param Collection $filters
     * @return bool
     */
    abstract protected function isSearchOne(Collection $filters);

    /**
     * @var JsonApiService
     */
    private $service;

    /**
     * AbstractSearch constructor.
     * @param JsonApiService $service
     */
    public function __construct(JsonApiService $service)
    {
        $this->service = $service;
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
        if (0 < $this->maxPerPage) {
            return true;
        }

        $paginator = $this->service->getPaginator();

        return is_int($paginator->getCurrentPage());
    }

    /**
     * @return int
     */
    protected function getPerPage()
    {
        $paginator = $this->service->getPaginator();

        return $paginator->getPerPage($this->perPage, $this->maxPerPage ?: null);
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

}
