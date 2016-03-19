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

namespace CloudCreativity\JsonApi\Pagination;

use CloudCreativity\JsonApi\Schema\PaginationLink;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator as IlluminatePaginator;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Parameters\ParametersInterface;

class Paginator
{

    /** Link names, defined in JSON API spec and compulsory */
    const LINK_FIRST = DocumentInterface::KEYWORD_FIRST;
    const LINK_LAST = DocumentInterface::KEYWORD_LAST;
    const LINK_PREV = DocumentInterface::KEYWORD_PREV;
    const LINK_NEXT = DocumentInterface::KEYWORD_NEXT;

    /** Default parameter key names */
    const PARAM_PAGE = 'number';
    const PARAM_PER_PAGE = 'size';

    /** Default meta keys names */
    const META_TOTAL = 'total';
    const META_LAST_PAGE = 'last';
    const META_FIRST_ITEM = 'from';
    const META_LAST_ITEM = 'to';

    /**
     * @var ParametersInterface
     */
    private $params;

    /**
     * @var string
     */
    private $pageKey;

    /**
     * @var string
     */
    private $perPageKey;

    /**
     * @var string
     */
    private $totalKey;

    /**
     * @var string
     */
    private $lastPageKey;

    /**
     * @var string
     */
    private $firstItemKey;

    /**
     * @var string
     */
    private $lastItemKey;

    /**
     * Paginator constructor.
     *
     * @param ParametersInterface $params
     *      the parsed parameters received from the client.
     * @param string|null $pageKey
     * @param string|null $perPageKey
     * @param string|null $totalKey
     * @param string|null $lastPageKey
     * @param string|null $firstItemKey
     * @param string|null $lastItemKey
     */
    public function __construct(
        ParametersInterface $params,
        $pageKey = null,
        $perPageKey = null,
        $totalKey = null,
        $lastPageKey = null,
        $firstItemKey = null,
        $lastItemKey = null
    ) {
        $this->params = $params;
        $this->pageKey = $pageKey;
        $this->perPageKey = $perPageKey;
        $this->totalKey = $totalKey;
        $this->lastPageKey = $lastPageKey;
        $this->firstItemKey = $firstItemKey;
        $this->lastItemKey = $lastItemKey;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    public function getPaginationParam($key, $default = null)
    {
        $params = $this->params->getPaginationParameters();

        return isset($params[$key]) ? $params[$key] : $default;
    }

    /**
     * Has the client requested pagination?
     *
     * Will return true if the client has sent either a page key or a per-page key in their pagination
     * parameters.
     *
     * @return bool
     */
    public function isPaginated()
    {
        $params = $this->params->getPaginationParameters();

        return isset($params[$this->getPageKey()]) || isset($params[$this->getPerPageKey()]);
    }

    /**
     * Get the requested page.
     *
     * If no page has been requested, defaults to this first page.
     *
     * @return int
     */
    public function getPage()
    {
        $page = (int) $this->getPaginationParam($this->getPageKey(), 1);

        return (0 < $page) ? $page : 1;
    }

    /**
     * Get the requested per-page amount.
     *
     * @param int $default
     *      the default to use if no per-page parameter has been provided by the client.
     * @param null $max
     *      the maximum allowed per-page, or null for no maximum.
     * @return int
     */
    public function getPerPage($default = 15, $max = null)
    {
        $perPage = (int) $this->getPaginationParam($this->getPerPageKey(), $default);

        if (is_int($max) && $perPage > $max) {
            $perPage = $max;
        }

        if (1 > $perPage) {
            $perPage = 1;
        }

        return (0 < $perPage) ? $perPage : 1;
    }

    /**
     * Get the key to use for current page in received parameters and meta.
     *
     * @return string
     */
    public function getPageKey()
    {
        return (is_string($this->pageKey) && !empty($this->pageKey)) ?
            $this->pageKey : self::PARAM_PAGE;
    }

    /**
     * Get the key to use for resources per-page in received parameters and meta.
     *
     * @return string
     */
    public function getPerPageKey()
    {
        return (is_string($this->perPageKey) && !empty($this->perPageKey)) ?
            $this->perPageKey : self::PARAM_PER_PAGE;
    }

    /**
     * Get the key to use for total resource in meta.
     *
     * @return string
     */
    public function getTotalKey()
    {
        return (is_string($this->totalKey) && !empty($this->totalKey)) ?
            $this->totalKey : self::META_TOTAL;
    }

    /**
     * Get the key to use for the last page in meta.
     *
     * @return string
     */
    public function getLastPageKey()
    {
        return (is_string($this->lastPageKey) && !empty($this->lastPageKey)) ?
            $this->lastPageKey : self::META_LAST_PAGE;
    }

    /**
     * Get the key to use for the first item in meta.
     *
     * @return string
     */
    public function getFirstItemKey()
    {
        return (is_string($this->firstItemKey) && !empty($this->firstItemKey)) ?
            $this->firstItemKey : self::META_FIRST_ITEM;
    }

    /**
     * @return string
     */
    public function getLastItemKey()
    {
        return (is_string($this->lastItemKey) && !empty($this->lastItemKey)) ?
            $this->lastItemKey : self::META_LAST_ITEM;
    }

    /**
     * Get pagination meta for the supplied Laravel paginator.
     *
     * @param IlluminatePaginator $query
     * @return array
     */
    public function getMeta(IlluminatePaginator $query)
    {
        $meta = [
            $this->getPageKey() => $query->currentPage(),
            $this->getPerPageKey() => $query->perPage(),
            $this->getFirstItemKey() => $query->firstItem(),
            $this->getLastItemKey() => $query->lastItem(),
        ];

        if ($query instanceof LengthAwarePaginator) {
            $meta[$this->getTotalKey()] = $query->total();
            $meta[$this->getLastPageKey()] = $query->lastPage();
        }

        return $meta;
    }

    /**
     * @param IlluminatePaginator $query
     * @param $subHref
     * @param null $meta
     * @param bool $treatAsHref
     * @return array
     */
    public function getLinks(IlluminatePaginator $query, $subHref, $meta = null, $treatAsHref = false)
    {
        $perPage = $query->perPage();
        $current = $query->currentPage();
        $last = null;
        $prev = (1 < $current) ? $current - 1 : null;
        $next = $current + 1;

        if ($query instanceof LengthAwarePaginator) {
            $last = $query->lastPage();
            $next = ($current < $last) ? $current + 1 : null;
        }

        return [
            self::LINK_FIRST => $this->generateLink(1, $perPage, $subHref, $meta, $treatAsHref),
            self::LINK_LAST => $this->generateLink($last, $perPage, $subHref, $meta, $treatAsHref),
            self::LINK_PREV => $this->generateLink($prev, $perPage, $subHref, $meta, $treatAsHref),
            self::LINK_NEXT => $this->generateLink($next, $perPage, $subHref, $meta, $treatAsHref),
        ];
    }

    /**
     * @param $page
     * @param $perPage
     * @param $subHref
     * @param null $meta
     * @param bool $treatAsHref
     * @return PaginationLink|null
     */
    protected function generateLink($page, $perPage, $subHref, $meta = null, $treatAsHref = false)
    {
        if (is_null($page)) {
            return null;
        }

        $pagination = [
            $this->getPageKey() => $page,
            $this->getPerPageKey() => $perPage,
        ];

        return new PaginationLink(
            $subHref,
            $pagination,
            $this->params->getSortParameters(),
            $this->params->getFilteringParameters(),
            $meta,
            $treatAsHref
        );
    }
}
