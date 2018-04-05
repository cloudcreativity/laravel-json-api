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

namespace CloudCreativity\JsonApi\Utils;

use CloudCreativity\JsonApi\Contracts\Document\MutableErrorInterface;
use CloudCreativity\JsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\JsonApi\Document\Error;
use CloudCreativity\JsonApi\Exceptions\MutableErrorCollection as Errors;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

/**
 * Class ErrorCreatorTrait
 *
 * @package CloudCreativity\JsonApi
 */
trait ErrorCreatorTrait
{

    /**
     * @var Errors
     */
    private $errors;

    /**
     * @return ErrorRepositoryInterface
     */
    abstract protected function getErrorRepository();

    /**
     * @return Errors
     */
    public function getErrors()
    {
        if (!$this->errors instanceof Errors) {
            $this->errors = new Errors();
        }

        return $this->errors;
    }

    /**
     * @param ErrorInterface|string $error
     * @param array $values
     * @return MutableErrorInterface
     *      the error that was added.
     */
    public function addError($error, array $values = [])
    {
        if ($error instanceof ErrorInterface) {
            $error = Error::cast($error);
        } else {
            $error = $this->getErrorRepository()->error($error, $values);
        }

        $this->getErrors()->add($error);

        return $error;
    }

    /**
     * @param $error
     * @param $pointer
     * @param array $values
     * @return MutableErrorInterface
     */
    public function addErrorWithPointer($error, $pointer, array $values = [])
    {
        $error = $this->addError($error, $values);
        $error->setSourcePointer($pointer);

        return $error;
    }

    /**
     * @param $error
     * @param $parameter
     * @param array $values
     * @return MutableErrorInterface
     */
    public function addErrorWithParameter($error, $parameter, array $values = [])
    {
        $error = $this->addError($error, $values);
        $error->setSourceParameter($parameter);

        return $error;
    }

    /**
     * Clear all errors
     *
     * @return $this
     */
    protected function reset()
    {
        $this->errors = null;

        return $this;
    }
}
