<?php

/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Utils;

use Closure;
use CloudCreativity\LaravelJsonApi\Document\Error;
use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidArgumentException;
use Illuminate\Contracts\Support\MessageBag;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

/**
 * Class ErrorBag
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ErrorBag extends AbstractErrorBag
{

    /**
     * @var Error
     */
    private $prototype;

    /**
     * @var string|null
     */
    private $sourcePrefix;

    /**
     * @var bool
     */
    private $isParameter;

    /**
     * @var bool
     */
    private $dasherize;

    /**
     * @var array|Closure|ResourceObject
     */
    private $keyMap;

    /**
     * Fluent constructor.
     *
     * @param MessageBag $messages
     * @param ErrorInterface|null $prototype
     * @return ErrorBag
     */
    public static function create(MessageBag $messages, ErrorInterface $prototype = null)
    {
        return new self($messages, $prototype);
    }

    /**
     * ErrorBag constructor.
     *
     * @param MessageBag $messages
     * @param ErrorInterface|null $prototype
     *      an error prototype that the source and detail from the message bag should be set on.
     * @param string|null $sourcePrefix
     * @param bool $isParameter
     *      true to set error source as a parameter, rather than a pointer.
     */
    public function __construct(
        MessageBag $messages,
        ErrorInterface $prototype = null,
        $sourcePrefix = null,
        $isParameter = false
    ) {
        parent::__construct($messages);
        $this->prototype = $prototype ? Error::cast($prototype) : new Error();
        $this->sourcePrefix = $sourcePrefix ? (string) $sourcePrefix : null;
        $this->isParameter = (bool) $isParameter;
        $this->dasherize = false;
        $this->keyMap = [];
    }

    /**
     * Set the title for the JSON API errors.
     *
     * @param string $title
     * @return $this
     */
    public function withTitle($title)
    {
        $this->prototype->setTitle($title);

        return $this;
    }

    /**
     * Set the status for the JSON API errors.
     *
     * @param int|string $status
     * @return $this
     */
    public function withStatus($status)
    {
        $this->prototype->setStatus($status);

        return $this;
    }

    /**
     * Set the code for the JSON API errors.
     *
     * @param int|string $code
     * @return $this
     */
    public function withCode($code)
    {
        $this->prototype->setCode($code);

        return $this;
    }

    /**
     * Set the meta for the JSON API errors.
     *
     * @param mixed $meta
     * @return $this
     */
    public function withMeta($meta)
    {
        $this->prototype->setMeta($meta);

        return $this;
    }

    /**
     * Set the links for the JSON API errors.
     *
     * @param array $links
     * @return $this
     */
    public function withLinks(array $links)
    {
        $this->prototype->setLinks($links);

        return $this;
    }

    /**
     * @param $prefix
     * @return self
     */
    public function withSourcePrefix($prefix)
    {
        $copy = clone $this;
        $copy->sourcePrefix = $prefix ? (string) $prefix : null;

        return $copy;
    }

    /**
     * @return self
     */
    public function withDasherizedKeys()
    {
        $copy = clone $this;
        $copy->dasherize = true;

        return $copy;
    }

    /**
     * @return self
     */
    public function withPointers()
    {
        $copy = clone $this;
        $copy->isParameter = false;

        return $copy;
    }

    /**
     * @return self
     */
    public function withParameters()
    {
        $copy = clone $this;
        $copy->isParameter = true;

        return $copy;
    }

    /**
     * @param array|Closure|ResourceObject $map
     * @return self
     */
    public function withKeyMap($map)
    {
        if (!is_array($map) && !$map instanceof Closure && !$map instanceof ResourceObject) {
            throw new InvalidArgumentException('Expecting an array, closure or resource object.');
        }

        $copy = clone $this;
        $copy->keyMap = $map;

        return $copy;
    }

    /**
     * @param string $key
     * @param string $detail
     * @return Error
     */
    protected function createError($key, $detail)
    {
        $error = clone $this->prototype;
        $error->setDetail($detail);

        if ($this->isParameter) {
            $error->setSourceParameter($this->createSourceParameter($key));
        } else {
            $error->setSourcePointer($this->createSourcePointer($key));
        }

        return $error;
    }

    /**
     * @param $key
     * @return string
     */
    protected function createSourcePointer($key)
    {
        if ($this->keyMap instanceof ResourceObject) {
            return $this->keyMap->pointer($key, '/data');
        }

        $key = $this->normalizeKey($key, '/');

        return $this->sourcePrefix ? sprintf('%s/%s', $this->sourcePrefix, $key) : $key;
    }

    /**
     * @param $key
     * @return string
     */
    protected function createSourceParameter($key)
    {
        $key = $this->normalizeKey($key);

        return $this->sourcePrefix ? sprintf('%s.%s', $this->sourcePrefix, $key) : $key;
    }

    /**
     * @param $key
     * @param string $glue
     * @return string
     */
    private function normalizeKey($key, $glue = '.')
    {
        $key = $this->mapKey($key);

        return collect(explode('.', $key))->map(function ($key) {
            return $this->dasherize ? Str::dasherize($key) : $key;
        })->implode($glue);
    }

    /**
     * @param string $key
     * @return string
     */
    private function mapKey($key)
    {
        if ($this->keyMap instanceof Closure) {
            return call_user_func($this->keyMap, $key);
        }

        return isset($this->keyMap[$key]) ? $this->keyMap[$key] : $key;
    }
}
