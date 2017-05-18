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

namespace CloudCreativity\LaravelJsonApi\Store;

use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Document\DocumentInterface;

/**
 * Trait FindsManyResources
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait FindsManyResources
{

    /**
     * @return string
     */
    protected function getFindManyKey()
    {
        $key = property_exists($this, 'findManyFilter') ? $this->findManyFilter : null;

        return $key ?: DocumentInterface::KEYWORD_ID;
    }

    /**
     * Do the filters contain a `find-many` parameter?
     *
     * @param Collection $filters
     * @return bool
     */
    protected function isFindMany(Collection $filters)
    {
        return $filters->has($this->getFindManyKey());
    }

    /**
     * @param Collection $filters
     * @return array
     */
    protected function extractIds(Collection $filters)
    {
        $ids = $filters->get($this->getFindManyKey());

        return $this->normalizeIds($ids);
    }

    /**
     * @param $ids
     * @return array
     */
    protected function normalizeIds($ids)
    {
        return is_array($ids) ? $ids : explode(',', (string) $ids);
    }
}
