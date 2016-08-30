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

use CloudCreativity\JsonApi\Contracts\Http\ApiInterface;
use Illuminate\Support\Arr;

/**
 * Class PaginatorConfiguration
 * @package CloudCreativity\LaravelJsonApi
 */
class PageMeta
{

    /** Default Meta Settings */
    const DEFAULT_META_CURRENT_PAGE = 'current-page';
    const DEFAULT_META_PER_PAGE = 'per-page';
    const DEFAULT_META_FIRST_ITEM = 'from';
    const DEFAULT_META_LAST_ITEM = 'to';
    const DEFAULT_META_TOTAL = 'total';
    const DEFAULT_META_LAST_PAGE = 'last-page';

    /**
     * @var ApiInterface
     */
    private $api;

    /**
     * PageMeta constructor.
     * @param ApiInterface $api
     */
    public function __construct(ApiInterface $api)
    {
        $this->api = $api;
    }

    /**
     * @return string
     */
    public function getCurrentPage()
    {
        return $this->get('current-page') ?: static::DEFAULT_META_CURRENT_PAGE;
    }

    /**
     * @return string
     */
    public function getPerPage()
    {
        return $this->get('per-page') ?: static::DEFAULT_META_PER_PAGE;
    }

    /**
     * @return string
     */
    public function getFirstItem()
    {
        return $this->get('first-item') ?: static::DEFAULT_META_FIRST_ITEM;
    }

    /**
     * @return string
     */
    public function getLastItem()
    {
        return $this->get('last-item') ?: static::DEFAULT_META_LAST_ITEM;
    }

    /**
     * @return string
     */
    public function getTotal()
    {
        return $this->get('total') ?: static::DEFAULT_META_TOTAL;
    }

    /**
     * @return string
     */
    public function getLastPage()
    {
        return $this->get('last-page') ?: static::DEFAULT_META_LAST_PAGE;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function get($key, $default = null)
    {
        $key = sprintf('paging-meta.%s', $key);

        return Arr::get($this->api->getOptions(), $key, $default);
    }

}
