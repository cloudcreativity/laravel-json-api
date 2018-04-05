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

use CloudCreativity\Utils\Object\StandardObject;
use CloudCreativity\Utils\Object\StandardObjectInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

/**
 * Class MetaMemberTrait
 *
 * @package CloudCreativity\JsonApi
 */
trait MetaMemberTrait
{

    /**
     * Get the meta member of the document.
     *
     * @return StandardObjectInterface
     * @throws RuntimeException
     *      if the meta member is present and is not an object or null.
     */
    public function getMeta()
    {
        $meta = $this->hasMeta() ? $this->get(DocumentInterface::KEYWORD_META) : new StandardObject();

        if (!is_null($meta) && !$meta instanceof StandardObjectInterface) {
            throw new RuntimeException('Data member is not an object.');
        }

        return $meta;
    }

    /**
     * @return bool
     */
    public function hasMeta()
    {
        return $this->has(DocumentInterface::KEYWORD_META);
    }

}
