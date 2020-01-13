<?php

/**
 * Copyright 2020 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Exceptions\MutableErrorCollection as Errors;
use Countable;
use Generator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Support\MessageProvider;
use IteratorAggregate;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

/**
 * Class AbstractErrorBag
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0 use the error translator instead.
 */
abstract class AbstractErrorBag implements Countable, IteratorAggregate, MessageProvider, Arrayable
{

    /**
     * @var MessageBag
     */
    private $messages;

    /**
     * Create a JSON API error for the supplied message key and detail.
     *
     * @param string $key
     * @param string $detail
     * @return ErrorInterface
     */
    abstract protected function createError($key, $detail);

    /**
     * AbstractErrorBag constructor.
     *
     * @param MessageBag $messages
     */
    public function __construct(MessageBag $messages)
    {
        $this->messages = $messages;
    }

    /**
     * @return Generator
     */
    public function getIterator()
    {
        foreach ($this->messages->toArray() as $key => $messages) {
            foreach ($messages as $detail) {
                yield $this->createError($key, $detail);
            }
        }
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->messages->count();
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return $this->messages->isEmpty();
    }

    /**
     * @return MessageBag
     */
    public function getMessageBag()
    {
        return $this->messages;
    }

    /**
     * @return Errors
     */
    public function getErrors()
    {
        return new Errors($this->all());
    }

    /**
     * @return ErrorInterface[]
     * @deprecated use `all`
     */
    public function toArray()
    {
        return $this->all();
    }

    /**
     * @return ErrorInterface[]
     */
    public function all()
    {
        return iterator_to_array($this);
    }
}
