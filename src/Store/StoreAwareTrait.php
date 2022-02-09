<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Store;

use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;

/**
 * Trait StoreAwareTrait
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait StoreAwareTrait
{

    /**
     * @var StoreInterface|null
     */
    private $store;

    /**
     * @param StoreInterface $store
     * @return $this
     */
    public function withStore(StoreInterface $store)
    {
        $this->store = $store;

        return $this;
    }

    /**
     * @return StoreInterface
     */
    protected function getStore(): StoreInterface
    {
        if (!$this->store) {
            $this->store = json_api()->getStore();
        }

        return $this->store;
    }
}
