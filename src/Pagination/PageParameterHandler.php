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

    /**
     * @var JsonApiService
     */
    private $service;

    /**
     * @var PaginatorConfiguration
     */
    private $config;

    /**
     * PageParameter constructor.
     * @param JsonApiService $service
     * @param PaginatorConfiguration $config
     */
    public function __construct(JsonApiService $service, PaginatorConfiguration $config)
    {
        $this->service = $service;
        $this->config = $config;
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
        $pageKey = $this->config->getParamPage();
        $perPageKey = $this->config->getParamPerPage();

        return Arr::has($params, $pageKey) || Arr::has($params, $perPageKey);
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
        $key = $this->config->getParamPage();
        $page = (int) $this->getParam($key, 1);

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
        $key = $this->config->getParamPerPage();
        $perPage = (int) $this->getParam($key, $default);

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
            $this->config->getParamPage(),
            $this->config->getParamPerPage(),
        ];
    }

    /**
     * @return array
     */
    protected function getParams()
    {
        return (array) $this
            ->service
            ->getRequest()
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
