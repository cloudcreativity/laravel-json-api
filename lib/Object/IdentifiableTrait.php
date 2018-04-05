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

namespace CloudCreativity\JsonApi\Object;

use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

/**
 * Class IdentifiableTrait
 *
 * @package CloudCreativity\JsonApi
 */
trait IdentifiableTrait
{

    /**
     * @return string
     * @throws RuntimeException
     *      if the type member is not present, or is not a string, or is an empty string.
     */
    public function getType()
    {
        if (!$this->has(DocumentInterface::KEYWORD_TYPE)) {
            throw new RuntimeException('Type member not present.');
        }

        $type = $this->get(DocumentInterface::KEYWORD_TYPE);

        if (!is_string($type) || empty($type)) {
            throw new RuntimeException('Type member is not a string, or is empty.');
        }

        return $type;
    }

    /**
     * @return bool
     */
    public function hasType()
    {
        return $this->has(DocumentInterface::KEYWORD_TYPE);
    }

    /**
     * @return string|int
     * @throws RuntimeException
     *      if the id member is not present, or is not a string/int, or is an empty string.
     */
    public function getId()
    {
        if (!$this->has(DocumentInterface::KEYWORD_ID)) {
            throw new RuntimeException('Id member not present.');
        }

        $id = $this->get(DocumentInterface::KEYWORD_ID);

        if (!is_string($id)) {
            throw new RuntimeException('Id member is not a string.');
        }

        if (empty($id)) {
            throw new RuntimeException('Id member is an empty string.');
        }

        return $id;
    }

    /**
     * @return bool
     */
    public function hasId()
    {
        return $this->has(DocumentInterface::KEYWORD_ID);
    }

}
