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

use Illuminate\Contracts\Config\Repository;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;

/**
 * Class PaginatorConfiguration
 * @package CloudCreativity\LaravelJsonApi
 */
class PaginatorConfiguration
{

    /** Default Parameter Settings */
    const DEFAULT_PARAM_PAGE = 'number';
    const DEFAULT_PARAM_PER_PAGE = 'size';

    /** Default Meta Settings */
    const DEFAULT_META_KEY = QueryParametersParserInterface::PARAM_PAGE;
    const DEFAULT_META_CURRENT_PAGE = 'current-page';
    const DEFAULT_META_PER_PAGE = 'per-page';
    const DEFAULT_META_FIRST_ITEM = 'from';
    const DEFAULT_META_LAST_ITEM = 'to';
    const DEFAULT_META_TOTAL = 'total';
    const DEFAULT_META_LAST_PAGE = 'last-page';

    /**
     * @var Repository
     */
    private $config;

    /**
     * PaginatorConfiguration constructor.
     * @param Repository $config
     */
    public function __construct(Repository $config)
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function getParamPage()
    {
        return $this->get('params.page') ?: static::DEFAULT_PARAM_PAGE;
    }

    /**
     * @return string
     */
    public function getParamPerPage()
    {
        return $this->get('params.per-page') ?: static::DEFAULT_PARAM_PER_PAGE;
    }

    /**
     * @return string
     */
    public function getMetaKey()
    {
        return $this->get('meta.key') ?: static::DEFAULT_META_KEY;
    }

    /**
     * @return string
     */
    public function getMetaCurrentPage()
    {
        return $this->get('meta.current-page') ?: static::DEFAULT_META_CURRENT_PAGE;
    }

    /**
     * @return string
     */
    public function getMetaPerPage()
    {
        return $this->get('meta.per-page') ?: static::DEFAULT_META_PER_PAGE;
    }

    /**
     * @return string
     */
    public function getMetaFirstItem()
    {
        return $this->get('meta.first-item') ?: static::DEFAULT_META_FIRST_ITEM;
    }

    /**
     * @return string
     */
    public function getMetaLastItem()
    {
        return $this->get('meta.last-item') ?: static::DEFAULT_META_LAST_ITEM;
    }

    /**
     * @return string
     */
    public function getMetaTotal()
    {
        return $this->get('meta.total') ?: static::DEFAULT_META_TOTAL;
    }

    /**
     * @return string
     */
    public function getMetaLastPage()
    {
        return $this->get('meta.last-page') ?: static::DEFAULT_META_LAST_PAGE;
    }

    /**
     * @param $key
     * @param null $default
     * @return mixed
     */
    protected function get($key, $default = null)
    {
        $key = 'json-api.pagination.' . $key;

        return $this->config->get($key, $default);
    }

}
