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

use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PaginatorInterface;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Pagination\Paginator as IlluminatePaginator;

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
     * Paginator constructor.
     * @param JsonApiService $service
     */
    public function __construct(JsonApiService $service)
    {
        $this->service = $service;
    }

    /**
     * @param IlluminatePaginator $results
     * @param null $meta
     * @return array|null
     * @todo customise keys in config
     * @todo allow meta to be set into nested config plus merge with existing meta.
     */
    public function getMeta(IlluminatePaginator $results, $meta = null)
    {
        $meta = [
            'number' => $results->currentPage(),
            'size' => $results->perPage(),
            'from' => $results->firstItem(),
            'to' => $results->lastItem(),
        ];

        if ($results instanceof LengthAwarePaginator) {
            $meta['total'] = $results->total();
            $meta['last'] = $results->lastPage();
        }

        return $meta;
    }

    /**
     * @param IlluminatePaginator $results
     * @return array
     * @todo
     */
    public function getLinks(IlluminatePaginator $results)
    {
        return [];
    }

}
