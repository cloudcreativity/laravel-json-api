<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

use CloudCreativity\JsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\JsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\JsonApi\Utils\Str;
use CloudCreativity\LaravelJsonApi\Contracts\Document\LinkFactoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\SortParameterInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;

class StandardStrategy implements PagingStrategyInterface
{

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
     * @var FactoryInterface
     */
    private $factory;

    /**
     * @var LinkFactoryInterface
     */
    private $linkFactory;

    /**
     * StandardStrategy constructor.
     *
     * @param FactoryInterface $factory
     * @param LinkFactoryInterface $linkFactory
     */
    public function __construct(FactoryInterface $factory, LinkFactoryInterface $linkFactory)
    {
        $this->factory = $factory;
        $this->linkFactory = $linkFactory;
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
    public function withUnderscoredMetaKeys()
    {
        $this->underscoreMeta = true;

        return $this;
    }

    /**
     * Set the key for the paging meta.
     *
     * Use this if you need to 'nest' the paging meta in a sub-key of the JSON API
     * document's top-level meta object.
     *
     * @param $key
     * @return $this
     */
    public function withMetaKey($key)
    {
        $this->metaKey = $key;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function paginate($query, EncodingParametersInterface $parameters)
    {
        $pageParameters = new Collection((array) $parameters->getPaginationParameters());
        $paginator = $this->query($query, $pageParameters);

        return $this->createPage($paginator, $parameters);
    }

    /**
     * @return string
     */
    protected function getPageKey()
    {
        return $this->pageKey ?: 'number';
    }

    /**
     * @param Collection $collection
     * @return int
     */
    protected function getPerPage(Collection $collection)
    {
        return $collection->get($this->getPerPageKey());
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
     * @return string
     */
    protected function getPerPageKey()
    {
        return $this->perPageKey ?: 'size';
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
     * @return string
     */
    protected function getMetaKey()
    {
        return $this->metaKey ?: null;
    }

    /**
     * @param mixed $query
     * @param Collection $pagingParameters
     * @return mixed
     */
    protected function query($query, Collection $pagingParameters)
    {
        $pageName = $this->getPageKey();
        $size = $this->getPerPage($pagingParameters) ?: $this->getDefaultPerPage($query);
        $cols = $this->getColumns();

        return ($this->isSimplePagination() && method_exists($query, 'simplePaginate')) ?
            $query->simplePaginate($size, $cols, $pageName) :
            $query->paginate($size, $cols, $pageName);
    }

    /**
     * @param Paginator $paginator
     * @param EncodingParametersInterface $parameters
     * @return PageInterface
     */
    protected function createPage(Paginator $paginator, EncodingParametersInterface $parameters)
    {
        $params = $this->buildParams($parameters);

        return $this->factory->createPage(
            $paginator,
            $this->createFirstLink($paginator, $params),
            $this->createPreviousLink($paginator, $params),
            $this->createNextLink($paginator, $params),
            $this->createLastLink($paginator, $params),
            $this->createMeta($paginator),
            $this->getMetaKey()
        );
    }

    /**
     * @param Paginator $paginator
     * @param array $params
     * @return LinkInterface
     */
    protected function createFirstLink(Paginator $paginator, array $params)
    {
        return $this->createLink(1, $paginator->perPage(), $params);
    }

    /**
     * @param Paginator $paginator
     * @param array $params
     * @return LinkInterface|null
     */
    protected function createPreviousLink(Paginator $paginator, array $params)
    {
        $previous = $paginator->currentPage() - 1;

        return $previous ? $this->createLink($previous, $paginator->perPage(), $params) : null;
    }

    /**
     * @param Paginator $paginator
     * @param array $params
     * @return LinkInterface|null
     */
    protected function createNextLink(Paginator $paginator, array $params)
    {
        $next = $paginator->currentPage() + 1;

        if ($paginator instanceof LengthAwarePaginator && $next > $paginator->lastPage()) {
            return null;
        }

        return $this->createLink($next, $paginator->perPage(), $params);
    }

    /**
     * @param Paginator $paginator
     * @param array $params
     * @return LinkInterface|null
     */
    protected function createLastLink(Paginator $paginator, array $params)
    {
        if (!$paginator instanceof LengthAwarePaginator) {
            return null;
        }

        return $this->createLink($paginator->lastPage(), $paginator->perPage(), $params);
    }

    /**
     * Build parameters that are to be included with pagination links.
     *
     * @param EncodingParametersInterface $parameters
     * @return array
     */
    protected function buildParams(EncodingParametersInterface $parameters)
    {
        return array_filter([
            QueryParametersParserInterface::PARAM_FILTER =>
                $parameters->getFilteringParameters(),
            QueryParametersParserInterface::PARAM_SORT =>
                $this->buildSortParams((array) $parameters->getSortParameters())
        ]);
    }

    /**
     * @param int $page
     * @param int $perPage
     * @param array $parameters
     * @param array|object|null $meta
     * @return LinkInterface
     */
    protected function createLink($page, $perPage, array $parameters = [], $meta = null)
    {
        return $this->linkFactory->current(array_merge($parameters, [
            QueryParametersParserInterface::PARAM_PAGE => [
                $this->getPageKey() => $page,
                $this->getPerPageKey() => $perPage,
            ],
        ]), $meta);
    }

    /**
     * @param Paginator $paginator
     * @return array
     */
    protected function createMeta(Paginator $paginator)
    {
        $meta = [
            $this->normalizeMetaKey('current-page') => $paginator->currentPage(),
            $this->normalizeMetaKey('per-page') => $paginator->perPage(),
            $this->normalizeMetaKey('from') => $paginator->firstItem(),
            $this->normalizeMetaKey('to') => $paginator->lastItem(),
        ];

        if ($paginator instanceof LengthAwarePaginator) {
            $meta[$this->normalizeMetaKey('total')] = $paginator->total();
            $meta[$this->normalizeMetaKey('last-page')] = $paginator->lastPage();
        }

        return $meta;
    }

    /**
     * @param $key
     * @return string
     */
    protected function normalizeMetaKey($key)
    {
        return $this->underscoreMeta ? Str::underscore($key) : $key;
    }

    /**
     * @param SortParameterInterface[] $parameters
     * @return string|null
     */
    private function buildSortParams(array $parameters)
    {
        $sort = array_map(function (SortParameterInterface $param) {
            return (string) $param;
        }, $parameters);

        return !empty($sort) ? implode(',', $sort) : null;
    }
}
