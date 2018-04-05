<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Contracts\Pagination;

use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;

/**
 * Interface PageInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface PageInterface
{

    const LINK_FIRST = DocumentInterface::KEYWORD_FIRST;
    const LINK_PREV = DocumentInterface::KEYWORD_PREV;
    const LINK_NEXT = DocumentInterface::KEYWORD_NEXT;
    const LINK_LAST = DocumentInterface::KEYWORD_LAST;

    /**
     * Get the page's data.
     *
     * @return mixed
     */
    public function getData();

    /**
     * Get a link to the first page.
     *
     * @return LinkInterface|null
     */
    public function getFirstLink();

    /**
     * Get a link to the previous page.
     *
     * @return LinkInterface|null
     */
    public function getPreviousLink();

    /**
     * Get a link to the next page.
     *
     * @return LinkInterface|null
     */
    public function getNextLink();

    /**
     * Get a link to the last page.
     *
     * @return LinkInterface|null
     */
    public function getLastLink();

    /**
     * Get pagination meta.
     *
     * @return object|array|null
     */
    public function getMeta();

    /**
     * Get the key into which page meta must be placed.
     *
     * @return string|null
     */
    public function getMetaKey();

}
