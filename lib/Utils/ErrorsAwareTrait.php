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

use CloudCreativity\JsonApi\Exceptions\MutableErrorCollection as Errors;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * Class ErrorsAwareTrait
 *
 * @package CloudCreativity\JsonApi
 */
trait ErrorsAwareTrait
{

    /**
     * @var Errors|null
     */
    private $errors;

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
     * @param ErrorInterface $error
     * @return $this
     */
    protected function addError(ErrorInterface $error)
    {
        $this->getErrors()->add($error);

        return $this;
    }

    /**
     * @param ErrorCollection|ErrorInterface[] $errors
     * @return $this
     */
    protected function addErrors($errors)
    {
        /** @var ErrorInterface $error */
        foreach ($errors as $error) {
            $this->getErrors()->add($error);
        }

        return $this;
    }

    /**
     * @return $this
     */
    protected function reset()
    {
        $this->errors = null;

        return $this;
    }
}
