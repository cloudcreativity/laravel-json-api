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

use CloudCreativity\JsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Contracts\Document\LinkFactoryInterface;
use CloudCreativity\LaravelJsonApi\Document\GeneratesLinks;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\SortParameterInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;

/**
 * Class Paginator
 * @package CloudCreativity\LaravelJsonApi
 */
class Page implements PageInterface
{

    use GeneratesLinks;

    /**
     * @var JsonApiService
     */
    private $service;

    /**
     * @var Paginator|null
     */
    private $data;

    /**
     * Page constructor.
     * @param JsonApiService $service
     */
    public function __construct(JsonApiService $service)
    {
        $this->service = $service;
    }

    /**
     * @param Paginator $data
     * @return $this
     */
    public function setData(Paginator $data)
    {
        $this->data = $data;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        if (!$this->data) {
            throw new RuntimeException('No paginated data set.');
        }

        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getMeta()
    {
        $pageMeta = new PageMeta($this->service->getApi());
        $results = $this->getData();

        $page = [
            $pageMeta->getCurrentPage() => $results->currentPage(),
            $pageMeta->getPerPage() => $results->perPage(),
            $pageMeta->getFirstItem() => $results->firstItem(),
            $pageMeta->getLastItem() => $results->lastItem(),
        ];

        if ($results instanceof LengthAwarePaginator) {
            $page[$pageMeta->getTotal()] = $results->total();
            $page[$pageMeta->getLastPage()] = $results->lastPage();
        }

        return $page;
    }

    /**
     * @inheritDoc
     */
    public function getLinks()
    {
        /** @var Paginator $results */
        $results = $this->getData();
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

        return array_filter([
            self::LINK_FIRST => $this->createLink(1, $perPage, $params),
            self::LINK_PREV => $this->createLink($previousPage, $perPage, $params),
            self::LINK_NEXT => $this->createLink($nextPage, $perPage, $params),
            self::LINK_LAST => $this->createLink($lastPage, $perPage, $params),
        ]);
    }

    /**
     * Build parameters that are to be included with pagination links.
     *
     * @return array
     */
    protected function buildParams()
    {
        $parameters = $this
            ->service
            ->getRequest()
            ->getParameters();

        return array_filter([
            QueryParametersParserInterface::PARAM_FILTER =>
                $parameters->getFilteringParameters(),
            QueryParametersParserInterface::PARAM_SORT =>
                $this->buildSortParams((array) $parameters->getSortParameters())
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

        $strategy = $this->service->getApi()->getPagingStrategy();

        return $this->linkTo()->current(array_merge($parameters, [
            QueryParametersParserInterface::PARAM_PAGE => [
                $strategy->getPage() => $page,
                $strategy->getPerPage() => $perPage,
            ],
        ]), $meta);
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
