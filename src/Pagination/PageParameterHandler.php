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

use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PageParameterHandlerInterface;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Support\Arr;

/**
 * Class PageParameterHandler
 * @package CloudCreativity\LaravelJsonApi
 */
class PageParameterHandler implements PageParameterHandlerInterface
{

    const DEFAULT_PAGE_KEY = 'number';
    const DEFAULT_PER_PAGE_KEY = 'size';

    /**
     * @var JsonApiService
     */
    private $service;

    /**
     * PageParameter constructor.
     * @param JsonApiService $service
     */
    public function __construct(JsonApiService $service)
    {
        $this->service = $service;
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
        $params = $this->getParams();

        return Arr::has($params, $this->getPageKey()) || Arr::has($params, $this->getPerPageKey());
    }

    /**
     * Get the requested page.
     *
     * If no page has been requested, defaults to this first page.
     *
     * @return int
     */
    public function getCurrentPage()
    {
        $page = (int) $this->getParam($this->getPageKey(), 1);

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
        $perPage = (int) $this->getParam($this->getPerPageKey(), $default);

        if (is_int($max) && $perPage > $max) {
            $perPage = $max;
        }

        if (1 > $perPage) {
            $perPage = 1;
        }

        return (0 < $perPage) ? $perPage : 1;
    }

    /**
     * @return array
     */
    public function getAllowedPagingParameters()
    {
        return [
            $this->getPageKey(),
            $this->getPerPageKey(),
        ];
    }

    /**
     * @return string
     */
    protected function getPageKey()
    {
        $key = config('json-api.pagination.params.page');

        return is_string($key) && !empty($key) ? $key : self::DEFAULT_PAGE_KEY;
    }

    /**
     * @return string
     */
    protected function getPerPageKey()
    {
        $key = config('json-api.pagination.params.per-page');

        return is_string($key) && !empty($key) ? $key : self::DEFAULT_PER_PAGE_KEY;
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return (array) $this
            ->service
            ->request()
            ->getEncodingParameters()
            ->getPaginationParameters();
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function getParam($key, $default = null)
    {
        return Arr::get($this->getParams(), $key, $default);
    }
}
