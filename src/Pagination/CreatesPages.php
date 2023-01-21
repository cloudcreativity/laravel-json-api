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
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\SortParameterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\LaravelJsonApi\Factories\Factory;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Neomerx\JsonApi\Contracts\Http\Query\BaseQueryParserInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;

/**
 * Trait CreatesPages
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait CreatesPages
{

    /**
     * @return string
     */
    protected function getPageKey()
    {
        $key = property_exists($this, 'pageKey') ? $this->pageKey : null;

        return $key ?: 'number';
    }

    /**
     * @return string
     */
    protected function getPerPageKey()
    {
        $key = property_exists($this, 'perPageKey') ? $this->perPageKey : null;

        return $key ?: 'size';
    }

    /**
     * @return string|null
     */
    protected function getMetaKey()
    {
        return property_exists($this, 'metaKey') ? $this->metaKey : null;
    }

    /**
     * @return bool
     */
    protected function isMetaUnderscored()
    {
        return property_exists($this, 'underscoreMeta') ? (bool) $this->underscoreMeta : false;
    }

    /**
     * @param Paginator $paginator
     * @param QueryParametersInterface $parameters
     * @return PageInterface
     */
    protected function createPage(Paginator $paginator, QueryParametersInterface $parameters)
    {
        $params = $this->buildParams($parameters);

        return app(Factory::class)->createPage(
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
     * @param QueryParametersInterface $parameters
     * @return array
     */
    protected function buildParams(QueryParametersInterface $parameters)
    {
        return array_filter([
            BaseQueryParserInterface::PARAM_FILTER =>
                $parameters->getFilteringParameters(),
            BaseQueryParserInterface::PARAM_SORT =>
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
        return json_api()->links()->current($meta, array_merge($parameters, [
            BaseQueryParserInterface::PARAM_PAGE => [
                $this->getPageKey() => $page,
                $this->getPerPageKey() => $perPage,
            ],
        ]));
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
        return $this->isMetaUnderscored() ? Str::underscore($key) : $key;
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
