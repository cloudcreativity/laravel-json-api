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

namespace CloudCreativity\JsonApi\Contracts\Document;

use Neomerx\JsonApi\Contracts\Document\DocumentInterface;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;

/**
 * Interface MutableErrorInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface MutableErrorInterface extends ErrorInterface
{

    /** Keywords for array exchanging */
    const ID = DocumentInterface::KEYWORD_ERRORS_ID;
    const STATUS = DocumentInterface::KEYWORD_ERRORS_STATUS;
    const CODE = DocumentInterface::KEYWORD_ERRORS_CODE;
    const TITLE = DocumentInterface::KEYWORD_ERRORS_TITLE;
    const DETAIL = DocumentInterface::KEYWORD_ERRORS_DETAIL;
    const META = DocumentInterface::KEYWORD_ERRORS_META;
    const SOURCE = DocumentInterface::KEYWORD_ERRORS_SOURCE;
    const LINKS = DocumentInterface::KEYWORD_ERRORS_LINKS;
    const LINKS_ABOUT = DocumentInterface::KEYWORD_ERRORS_ABOUT;

    /**
     * Set the error id.
     *
     * @param string|int|null $id
     * @return $this
     */
    public function setId($id);

    /**
     * Does the error have an id?
     *
     * @return bool
     */
    public function hasId();

    /**
     * Set links on the error, removing any existing links.
     *
     * @param array|null $links
     * @return $this
     */
    public function setLinks(array $links = null);

    /**
     * Add links to the error (merging with existing links).
     *
     * @param array|null $links
     * @return $this
     */
    public function addLinks(array $links);

    /**
     * Add a link to the error.
     *
     * @param string $key
     * @param LinkInterface $link
     * @return $this
     */
    public function addLink($key, LinkInterface $link);

    /**
     * Set the 'about' link on the error.
     *
     * @param LinkInterface $link
     * @return $this
     */
    public function setAboutLink(LinkInterface $link);

    /**
     * Set the error status.
     *
     * @param string|int|null $status
     * @return $this
     */
    public function setStatus($status);

    /**
     * Does the error have a status?
     *
     * @return bool
     */
    public function hasStatus();

    /**
     * Set the error code.
     *
     * @param string|int|null $code
     * @return $this
     */
    public function setCode($code);

    /**
     * Does the error have a code?
     *
     * @return bool
     */
    public function hasCode();

    /**
     * Set the error title.
     *
     * @param string|null $title
     * @return $this
     */
    public function setTitle($title);

    /**
     * Does the error have a title?
     *
     * @return bool
     */
    public function hasTitle();

    /**
     * Set the error detail.
     *
     * @param string|null $detail
     * @return $this
     */
    public function setDetail($detail);

    /**
     * Does the error have detail?
     *
     * @return bool
     */
    public function hasDetail();

    /**
     * Set the error source, removing any existing source.
     *
     * @param array|null $source
     * @return $this
     */
    public function setSource(array $source = null);

    /**
     * Set the error source pointer.
     *
     * @param string|null $pointer
     * @return $this
     */
    public function setSourcePointer($pointer);

    /**
     * Get the error source pointer.
     *
     * @return string|null
     */
    public function getSourcePointer();

    /**
     * Does the error have a source pointer?
     *
     * @return bool
     */
    public function hasSourcePointer();

    /**
     * Set the error source parameter.
     *
     * @param string|null $parameter
     * @return $this
     */
    public function setSourceParameter($parameter);

    /**
     * Get the error source parameter.
     *
     * @return string|null
     */
    public function getSourceParameter();

    /**
     * Does the error have a source parameter?
     *
     * @return bool
     */
    public function hasSourceParameter();

    /**
     * Set the error meta, removing any existing meta.
     *
     * @param array|null $meta
     * @return $this
     */
    public function setMeta(array $meta = null);

    /**
     * Add meta to any existing error meta.
     *
     * @param array $meta
     * @return $this
     */
    public function addMeta(array $meta);

    /**
     * Merge the provided error into this error.
     *
     * @param ErrorInterface $error
     * @return $this
     */
    public function merge(ErrorInterface $error);

    /**
     * Merge an array representation of an error into this error.
     *
     * @param array $input
     * @return $this
     */
    public function exchangeArray(array $input);

}
