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

namespace CloudCreativity\JsonApi\Http\Controllers;

use CloudCreativity\JsonApi\Pagination\Paginator;
use Illuminate\Pagination\AbstractPaginator;
use Neomerx\JsonApi\Contracts\Parameters\ParametersInterface;

trait PaginatedTrait
{

    /**
     * @return ParametersInterface
     */
    abstract public function getParameters();

    /**
     * @param string|null $pageKey
     * @param string|null $perPageKey
     * @param string|null $totalKey
     * @param string|null $lastPageKey
     * @param string|null $firstItemKey
     * @param string|null $lastItemKey
     * @return Paginator
     */
    public function getPaginator(
        $pageKey = null,
        $perPageKey = null,
        $totalKey = null,
        $lastPageKey = null,
        $firstItemKey = null,
        $lastItemKey = null
    ) {
        $paginator = new Paginator(
            $this->getParameters(),
            $pageKey,
            $perPageKey,
            $totalKey,
            $lastPageKey,
            $firstItemKey,
            $lastItemKey
        );

        // Override the page resolver so that it uses the JSON-API page parameter.
        AbstractPaginator::currentPageResolver(function () use ($paginator) {
            return $paginator->getPage();
        });

        return $paginator;
    }
}
