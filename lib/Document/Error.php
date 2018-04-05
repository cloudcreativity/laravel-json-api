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

namespace CloudCreativity\JsonApi\Document;

use CloudCreativity\JsonApi\Contracts\Document\MutableErrorInterface;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Document\Link;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * Class Error
 *
 * @package CloudCreativity\JsonApi
 */
class Error implements MutableErrorInterface
{

    /**
     * @var int|string|null
     */
    private $id;

    /**
     * @var array
     */
    private $links;

    /**
     * @var string|int|null
     */
    private $status;

    /**
     * @var string|null
     */
    private $code;

    /**
     * @var string|null
     */
    private $title;

    /**
     * @var string|null
     */
    private $detail;

    /**
     * @var array
     */
    private $source;

    /**
     * @var array
     */
    private $meta;

    /**
     * @param $error
     * @return Error
     */
    public static function cast($error)
    {
        if ($error instanceof self) {
            return $error;
        } elseif (!$error instanceof ErrorInterface) {
            throw new InvalidArgumentException('Expecting an error object.');
        }

        return new self(
            $error->getId(),
            $error->getLinks(),
            $error->getStatus(),
            $error->getCode(),
            $error->getTitle(),
            $error->getDetail(),
            $error->getSource(),
            $error->getMeta()
        );
    }

    /**
     * Create an error object from an array.
     *
     * @param array|object $input
     * @return Error
     */
    public static function create($input = [])
    {
        $error = new self();
        $error->exchangeArray((array) $input);

        return $error;
    }

    /**
     * Create an error collection from an array of error arrays.
     *
     * @param array $input
     * @return ErrorCollection
     */
    public static function createMany(array $input)
    {
        $errors = new ErrorCollection();

        foreach ($input as $item) {
            $errors->add(self::create($item));
        }

        return $errors;
    }

    /**
     * @param int|string|null $id
     * @param array|null $links
     * @param int|string|null $status
     * @param int|string|null $code
     * @param string|null $title
     * @param string|null $detail
     * @param array|null $source
     * @param array|null $meta
     */
    public function __construct(
        $id = null,
        array $links = null,
        $status = null,
        $code = null,
        $title = null,
        $detail = null,
        array $source = null,
        array $meta = null
    ) {
        $this->setId($id);
        $this->setLinks($links);
        $this->setStatus($status);
        $this->setCode($code);
        $this->setTitle($title);
        $this->setDetail($detail);
        $this->setSource($source);
        $this->setMeta($meta);
    }

    /**
     * @inheritdoc
     */
    public function setId($id)
    {
        if (!is_int($id) && !is_string($id) && !is_null($id)) {
            throw new InvalidArgumentException('Expecting error id to be a string, integer or null.');
        }

        $this->id = $id;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @inheritdoc
     */
    public function hasId()
    {
        return !empty($this->id);
    }

    /**
     * @inheritdoc
     */
    public function setLinks(array $links = null)
    {
        $this->links = [];

        if ($links) {
            $this->addLinks($links);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addLinks(array $links = null)
    {
        foreach ((array) $links as $key => $link) {
            if (is_string($link)) {
                $link = new Link($link, null, true);
            }

            if (!$link instanceof LinkInterface) {
                throw new InvalidArgumentException('Expecting links to contain link objects.');
            }

            $this->addLink($key, $link);
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addLink($key, LinkInterface $link)
    {
        $this->links[$key] = $link;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function setAboutLink(LinkInterface $link)
    {
        $this->addLink(self::LINKS_ABOUT, $link);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getLinks()
    {
        return !empty($this->links) ? $this->links : null;
    }

    /**
     * @inheritdoc
     */
    public function setStatus($status)
    {
        if (!is_int($status) && !is_string($status) && !is_null($status)) {
            throw new InvalidArgumentException('Expecting error status to be a string, integer or null.');
        }

        $this->status = $status;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getStatus()
    {
        return $this->hasStatus() ? (string) $this->status : null;
    }

    /**
     * @inheritdoc
     */
    public function hasStatus()
    {
        return !empty($this->status);
    }

    /**
     * @inheritdoc
     */
    public function setCode($code)
    {
        if (!is_string($code) && !is_int($code) && !is_null($code)) {
            throw new InvalidArgumentException('Expecting error code to be a string, integer or null.');
        }

        $this->code = $code;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCode()
    {
        return $this->hasCode() ? $this->code : null;
    }

    /**
     * @inheritdoc
     */
    public function hasCode()
    {
        return !empty($this->code);
    }

    /**
     * @inheritdoc
     */
    public function setTitle($title)
    {
        if (!is_string($title) && !is_null($title)) {
            throw new InvalidArgumentException('Expecting error title to be a string or null.');
        }

        $this->title = $title;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTitle()
    {
        return $this->hasTitle() ? $this->title : null;
    }

    /**
     * @inheritdoc
     */
    public function hasTitle()
    {
        return !empty($this->title);
    }

    /**
     * @inheritdoc
     */
    public function setDetail($detail)
    {
        if (!is_string($detail) && !is_null($detail)) {
            throw new InvalidArgumentException('Expecting error detail to be a string or null.');
        }

        $this->detail = $detail;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getDetail()
    {
        return $this->hasDetail() ? $this->detail : null;
    }

    /**
     * @return bool
     */
    public function hasDetail()
    {
        return !empty($this->detail);
    }

    /**
     * @inheritdoc
     */
    public function setSource(array $source = null)
    {
        $this->source = (array) $source;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSource()
    {
        return !empty($this->source) ? $this->source : null;
    }

    /**
     * @param string|null $pointer
     * @return $this
     */
    public function setSourcePointer($pointer)
    {
        if (!is_string($pointer) && !is_null($pointer)) {
            throw new InvalidArgumentException('Expecting error source pointer to be a string or null');
        }

        if (empty($pointer)) {
            unset($this->source[self::SOURCE_POINTER]);
        } else {
            $this->source[self::SOURCE_POINTER] = $pointer;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSourcePointer()
    {
        return $this->hasSourcePointer() ? $this->source[self::SOURCE_POINTER] : null;
    }

    /**
     * @inheritdoc
     */
    public function hasSourcePointer()
    {
        return isset($this->source[self::SOURCE_POINTER]);
    }

    /**
     * @inheritdoc
     */
    public function setSourceParameter($parameter)
    {
        if (!is_string($parameter) && !is_null($parameter)) {
            throw new InvalidArgumentException('Expecting source parameter to be a string or null');
        }

        if (empty($parameter)) {
            unset($this->source[self::SOURCE_PARAMETER]);
        } else {
            $this->source[self::SOURCE_PARAMETER] = $parameter;
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSourceParameter()
    {
        return $this->hasSourceParameter() ? $this->source[self::SOURCE_PARAMETER] : null;
    }

    /**
     * @inheritdoc
     */
    public function hasSourceParameter()
    {
        return isset($this->source[self::SOURCE_PARAMETER]);
    }

    /**
     * @inheritdoc
     */
    public function setMeta(array $meta = null)
    {
        $this->meta = (array) $meta;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function addMeta(array $meta)
    {
        $this->meta = array_replace_recursive($this->meta, $meta);

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getMeta()
    {
        return !empty($this->meta) ? $this->meta : null;
    }

    /**
     * @inheritdoc
     */
    public function merge(ErrorInterface $error)
    {
        // Id
        if ($error->getId()) {
            $this->setId($error->getId());
        }

        // Links
        if ($error->getLinks()) {
            $this->addLinks($error->getLinks());
        }

        // Status
        if ($error->getStatus()) {
            $this->setStatus($error->getStatus());
        }

        // Code
        if ($error->getCode()) {
            $this->setCode($error->getCode());
        }

        // Title
        if ($error->getTitle()) {
            $this->setTitle($error->getTitle());
        }

        // Detail
        if ($error->getDetail()) {
            $this->setDetail($error->getDetail());
        }

        // Source
        if ($error->getSource()) {
            $this->setSource($error->getSource());
        }

        // Meta
        if ($error->getMeta()) {
            $this->addMeta($error->getMeta());
        }

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function exchangeArray(array $input)
    {
        // Id
        if (array_key_exists(self::ID, $input)) {
            $this->setId($input[self::ID]);
        }

        // Links
        if (array_key_exists(self::LINKS, $input)) {
            $this->addLinks((array) $input[self::LINKS]);
        }

        // About Link
        if (array_key_exists(self::LINKS_ABOUT, $input) && $input[self::LINKS_ABOUT] instanceof LinkInterface) {
            $this->setAboutLink($input[self::LINKS_ABOUT]);
        }

        // Status
        if (array_key_exists(self::STATUS, $input)) {
            $this->setStatus($input[self::STATUS]);
        }

        // Code
        if (array_key_exists(self::CODE, $input)) {
            $this->setCode($input[self::CODE]);
        }

        // Title
        if (array_key_exists(self::TITLE, $input)) {
            $this->setTitle($input[self::TITLE]);
        }

        // Detail
        if (array_key_exists(self::DETAIL, $input)) {
            $this->setDetail($input[self::DETAIL]);
        }

        // Source
        if (array_key_exists(self::SOURCE, $input)) {
            $this->setSource((array) $input[self::SOURCE]);
        }

        // Source Pointer
        if (array_key_exists(self::SOURCE_POINTER, $input)) {
            $this->setSourcePointer($input[self::SOURCE_POINTER]);
        }

        // Source Parameter
        if (array_key_exists(self::SOURCE_PARAMETER, $input)) {
            $this->setSourceParameter($input[self::SOURCE_PARAMETER]);
        }

        // Meta
        if (array_key_exists(self::META, $input)) {
            $this->addMeta((array) $input[self::META]);
        }

        return $this;
    }

}
