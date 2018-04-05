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

namespace CloudCreativity\JsonApi\Contracts\Object;

use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\Utils\Object\StandardObjectInterface;

/**
 * Interface MetaMemberInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface MetaMemberInterface
{

    /**
     * Get the meta member of the object.
     *
     * @return StandardObjectInterface
     * @throws RuntimeException
     *      if the meta member is present and is not an object.
     */
    public function getMeta();

    /**
     * Does the object have meta?
     *
     * @return bool
     */
    public function hasMeta();

}
