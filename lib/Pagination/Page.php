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

namespace CloudCreativity\JsonApi\Pagination;

use CloudCreativity\JsonApi\Contracts\Pagination\PageInterface;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;

/**
 * Class Page
 *
 * @package CloudCreativity\JsonApi
 */
class Page implements PageInterface
{

    /**
     * @var mixed
     */
    private $data;

    /**
     * @var LinkInterface|null
     */
    private $first;

    /**
     * @var LinkInterface|null
     */
    private $previous;

    /**
     * @var LinkInterface|null
     */
    private $next;

    /**
     * @var LinkInterface|null
     */
    private $last;

    /**
     * @var array|null|object
     */
    private $meta;

    /**
     * @var string|null
     */
    private $metaKey;

    /**
     * Page constructor.
     *
     * @param $data
     * @param LinkInterface|null $first
     * @param LinkInterface|null $previous
     * @param LinkInterface|null $next
     * @param LinkInterface|null $last
     * @param object|array|null $meta
     * @param string|null $metaKey
     */
    public function __construct(
        $data,
        LinkInterface $first = null,
        LinkInterface $previous = null,
        LinkInterface $next = null,
        LinkInterface $last = null,
        $meta = null,
        $metaKey = null
    ) {
        $this->data = $data;
        $this->first = $first;
        $this->previous = $previous;
        $this->next = $next;
        $this->last = $last;
        $this->meta = $meta;
        $this->metaKey = $metaKey;
    }

    /**
     * @inheritDoc
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @inheritDoc
     */
    public function getFirstLink()
    {
        return $this->first;
    }

    /**
     * @inheritDoc
     */
    public function getPreviousLink()
    {
        return $this->previous;
    }

    /**
     * @inheritDoc
     */
    public function getNextLink()
    {
        return $this->next;
    }

    /**
     * @inheritDoc
     */
    public function getLastLink()
    {
        return $this->last;
    }

    /**
     * @inheritDoc
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * @inheritDoc
     */
    public function getMetaKey()
    {
        return $this->metaKey;
    }


}
