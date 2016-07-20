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

use Illuminate\Contracts\Pagination\Paginator;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

/**
 * Interface PaginatorInterface
 * @package CloudCreativity\LaravelJsonApi
 */
interface PaginatorInterface
{

    const LINK_FIRST = DocumentInterface::KEYWORD_FIRST;
    const LINK_LAST = DocumentInterface::KEYWORD_LAST;
    const LINK_PREV = DocumentInterface::KEYWORD_PREV;
    const LINK_NEXT = DocumentInterface::KEYWORD_NEXT;

    /**
     * Add pagination meta to the supplied meta.
     *
     * @param Paginator $results
     * @param array|object|null $meta
     *      the meta to merge the pagination meta with.
     * @return array|object
     */
    public function addMeta(Paginator $results, $meta = null);

    /**
     * Add pagination links to the supplied links.
     *
     * @param Paginator $results
     * @param array $links
     *      the links to add the pagination links to.
     * @return array
     */
    public function addLinks(Paginator $results, array $links = []);
}
