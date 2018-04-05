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

namespace CloudCreativity\JsonApi\Exceptions;

use CloudCreativity\JsonApi\Contracts\Document\MutableErrorInterface;
use CloudCreativity\JsonApi\Document\Error;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * Class MutableErrorCollection
 *
 * @package CloudCreativity\JsonApi
 */
class MutableErrorCollection extends ErrorCollection
{

    /**
     * @param ErrorInterface|ErrorInterface[]|ErrorCollection $errors
     * @return MutableErrorCollection
     */
    public static function cast($errors)
    {
        if ($errors instanceof self) {
            return $errors;
        } elseif ($errors instanceof ErrorCollection) {
            $errors = $errors->getArrayCopy();
        } elseif ($errors instanceof ErrorInterface) {
            $errors = [$errors];
        } elseif (!is_array($errors)) {
            throw new InvalidArgumentException('Expecting an error collection or an array of error objects.');
        }

        return new self($errors);
    }

    /**
     * ErrorCollection constructor.
     *
     * @param array $errors
     */
    public function __construct(array $errors = [])
    {
        $this->addMany($errors);
    }

    /**
     * @param array $errors
     * @return $this
     */
    public function addMany(array $errors)
    {
        foreach ($errors as $error) {

            if (!$error instanceof ErrorInterface) {
                throw new InvalidArgumentException('Expecting only error objects.');
            }

            $this->add($error);
        }

        return $this;
    }

    /**
     * @param ErrorInterface $error
     * @return $this
     */
    public function add(ErrorInterface $error)
    {
        parent::add(Error::cast($error));

        return $this;
    }

    /**
     * @param ErrorCollection $errors
     * @return $this
     */
    public function merge(ErrorCollection $errors)
    {
        /** @var ErrorInterface $error */
        foreach ($errors as $error) {
            $this->add($error);
        }

        return $this;
    }

    /**
     * Get the most applicable HTTP status code.
     *
     * From the spec:
     * When a server encounters multiple problems for a single request, the most generally applicable HTTP error
     * code SHOULD be used in the response. For instance, 400 Bad Request might be appropriate for multiple
     * 4xx errors or 500 Internal Server Error might be appropriate for multiple 5xx errors.
     *
     * @param string|int|null $default
     *      the default to use if an error status cannot be resolved. Defaults to 400 Bad Request
     * @return string|int
     */
    public function getHttpStatus($default = null)
    {
        $default = $default ?: JsonApiException::DEFAULT_HTTP_CODE;
        $request = null;
        $internal = null;

        /** @var MutableErrorInterface $error */
        foreach ($this as $error) {

            $status = $error->getStatus();

            if (400 <= $status && 499 >= $status) {
                $request = is_null($request) ? $status : ($request == $status) ? $status : 400;
            } elseif (500 <= $status && 599 >= $status) {
                $internal = is_null($internal) ? $status : ($internal == $status) ? $status : 500;
            }
        }

        if (!$request && !$internal) {
            return $default;
        } elseif ($request && $internal) {
            return 500;
        }

        return $request ?: $internal;
    }
}
