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

namespace CloudCreativity\LaravelJsonApi\Contracts\Pagination;

/**
 * Interface PageParameterHandlerInterface
 * @package CloudCreativity\LaravelJsonApi
 */
interface PageParameterHandlerInterface
{

    /**
     * Has the client requested pagination?
     *
     * @return bool
     */
    public function isPaginated();

    /**
     * Get the current page number.
     *
     * @return int
     */
    public function getCurrentPage();

    /**
     * Get the number of resources per-page.
     *
     * @param int $default
     *      the default to use if the client has not specified a per-page amount.
     * @param int|null $max
     *      the maximum allowed per-page, or null if no maximum.
     * @return int
     */
    public function getPerPage($default = 15, $max = null);

    /**
     * Get the keys that are allowed in the `page` parameter.
     *
     * @return string[]
     */
    public function getAllowedPagingParameters();
}
