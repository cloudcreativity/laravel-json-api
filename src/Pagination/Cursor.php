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

use Illuminate\Support\Arr;

/**
 * Class Cursor
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Cursor
{

    /**
     * @var string|null
     */
    private $before;

    /**
     * @var string|null
     */
    private $after;

    /**
     * @var int
     */
    private $limit;

    /**
     * Create a cursor from query parameters.
     *
     * @param array $parameters
     * @param string $beforeKey
     * @param string $afterKey
     * @param string $limitKey
     * @return Cursor
     */
    public static function create(
        array $parameters,
        $beforeKey = 'before',
        $afterKey = 'after',
        $limitKey = 'limit'
    ) {
        return new self(
            Arr::get($parameters, $beforeKey),
            Arr::get($parameters, $afterKey),
            Arr::get($parameters, $limitKey, 15)
        );
    }

    /**
     * Cursor constructor.
     *
     * @param null $before
     * @param null $after
     * @param int $limit
     */
    public function __construct($before = null, $after = null, $limit = 15)
    {
        $this->before = $before ?: null;
        $this->after = $after ?: null;
        $this->limit = 0 < $limit ? (int) $limit : 1;
    }

    /**
     * @return bool
     */
    public function isBefore()
    {
        return !is_null($this->before);
    }

    /**
     * @return string|null
     */
    public function getBefore()
    {
        return $this->before;
    }

    /**
     * @return bool
     */
    public function isAfter()
    {
        return !is_null($this->after) && !$this->isBefore();
    }

    /**
     * @return string|null
     */
    public function getAfter()
    {
        return $this->after;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

}
