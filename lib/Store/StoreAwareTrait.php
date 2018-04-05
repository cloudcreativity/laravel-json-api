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

namespace CloudCreativity\JsonApi\Store;

use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;

/**
 * Trait StoreAwareTrait
 *
 * @package CloudCreativity\JsonApi
 */
trait StoreAwareTrait
{

    /**
     * @var StoreInterface|null
     */
    private $store;

    /**
     * @param StoreInterface $store
     * @return void
     */
    public function withStore(StoreInterface $store)
    {
        $this->store = $store;
    }

    /**
     * @return StoreInterface
     */
    protected function store()
    {
        if (!$this->store) {
            throw new RuntimeException('No store injected.');
        }

        return $this->store;
    }
}
