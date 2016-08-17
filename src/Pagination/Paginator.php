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

namespace CloudCreativity\LaravelJsonApi\Pagination;

use CloudCreativity\LaravelJsonApi\Contracts\Document\LinkFactoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PaginatorInterface;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator as IlluminatePaginator;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\SortParameterInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;

/**
 * Class Paginator
 * @package CloudCreativity\LaravelJsonApi
 */
class Paginator implements PaginatorInterface
{

    /**
     * @var JsonApiService
     */
    private $service;

    /**
     * @var LinkFactoryInterface
     */
    private $links;

    /**
     * @var PaginatorConfiguration
     */
    private $config;

    /**
     * Paginator constructor.
     * @param JsonApiService $service
     * @param LinkFactoryInterface $links
     * @param PaginatorConfiguration $config
     */
    public function __construct(
        JsonApiService $service,
        LinkFactoryInterface $links,
        PaginatorConfiguration $config
    ) {
        $this->service = $service;
        $this->links = $links;
        $this->config = $config;
    }

    /**
     * @param IlluminatePaginator $results
     * @param array|object|null $meta
     *      the meta to add the pagination meta to.
     * @return array|object|null
     *      the combined meta.
     */
    public function addMeta(IlluminatePaginator $results, $meta = null)
    {
        $meta = $meta ?: [];

        $page = [
            $this->config->getMetaCurrentPage() => $results->currentPage(),
            $this->config->getMetaPerPage() => $results->perPage(),
            $this->config->getMetaFirstItem() => $results->firstItem(),
            $this->config->getMetaLastItem() => $results->lastItem(),
        ];

        if ($results instanceof LengthAwarePaginator) {
            $page[$this->config->getMetaTotal()] = $results->total();
            $page[$this->config->getMetaLastPage()] = $results->lastPage();
        }

        $key = $this->config->getMetaKey();

        if (is_array($meta)) {
            $meta[$key] = $page;
        } elseif (is_object($meta)) {
            $meta->{$key} = $page;
        }

        return $meta;
    }

    /**
     * @param IlluminatePaginator $results
     * @param array $links
     * @return array
     */
    public function addLinks(IlluminatePaginator $results, array $links = [])
    {
        $currentPage = $results->currentPage();
        $perPage = $results->perPage();
        $params = $this->buildParams();

        $previousPage = (1 < $currentPage) ? $currentPage - 1 : null;

        if ($results instanceof LengthAwarePaginator) {
            $lastPage = $results->lastPage();
            $nextPage = ($currentPage < $lastPage) ? $currentPage + 1 : null;
        } else {
            $lastPage = null;
            $nextPage = $currentPage + 1;
        }

        $page = array_filter([
            self::LINK_FIRST => $this->createLink(1, $perPage, $params),
            self::LINK_PREV => $this->createLink($previousPage, $perPage, $params),
            self::LINK_NEXT => $this->createLink($nextPage, $perPage, $params),
            self::LINK_LAST => $this->createLink($lastPage, $perPage, $params),
        ]);

        return array_merge($page, $links);
    }

    /**
     * Build parameters that are to be included with pagination links.
     *
     * @return array
     */
    protected function buildParams()
    {
        $encodingParameters = $this
            ->service
            ->getRequest()
            ->getEncodingParameters();

        return array_filter([
            QueryParametersParserInterface::PARAM_FILTER =>
                $encodingParameters->getFilteringParameters(),
            QueryParametersParserInterface::PARAM_SORT =>
                $this->buildSortParams((array) $encodingParameters->getSortParameters())
        ]);
    }

    /**
     * @param int|null $page
     * @param int $perPage
     * @param array $parameters
     * @param array|object|null $meta
     * @return LinkInterface|null
     */
    protected function createLink($page, $perPage, array $parameters = [], $meta = null)
    {
        if (!is_int($page)) {
            return null;
        }

        return $this->links->current(array_merge($parameters, [
            QueryParametersParserInterface::PARAM_PAGE => [
                $this->config->getParamPage() => $page,
                $this->config->getParamPerPage() => $perPage,
            ],
        ]), $meta);
    }

    /**
     * @param SortParameterInterface[] $parameters
     * @return string
     */
    private function buildSortParams(array $parameters)
    {
        $sort = [];

        /** @var SortParameterInterface $param */
        foreach ($parameters as $param) {
            $sort[] = $param->isAscending() ? $param->getField() : '-' . $param->getField();
        }

        return !empty($sort) ? implode(',', $sort) : null;
    }
}
