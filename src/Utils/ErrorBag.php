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

namespace CloudCreativity\LaravelJsonApi\Utils;

use CloudCreativity\JsonApi\Document\Error;
use CloudCreativity\JsonApi\Utils\Str;
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
    public function asPointers()
    {
        $copy = clone $this;
        $copy->isParameter = false;

        return $copy;
    }

    /**
     * @return self
     */
    public function asParameters()
    {
        $copy = clone $this;
        $copy->isParameter = true;

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
    protected function normalizeKey($key, $glue = '.')
    {
        return collect(explode('.', $key))->map(function ($key) {
            return $this->dasherize ? Str::dasherize($key) : $key;
        })->implode($glue);
    }
}
