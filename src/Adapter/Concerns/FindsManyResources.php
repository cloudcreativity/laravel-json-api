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

namespace CloudCreativity\LaravelJsonApi\Adapter\Concerns;

use Illuminate\Support\Collection;
use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use function is_array;
use function is_string;

/**
 * Trait FindsManyResources
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait FindsManyResources
{

    /**
     * Do the filters contain a `find-many` parameter?
     *
     * @param Collection $filters
     * @return bool
     */
    protected function isFindMany(Collection $filters)
    {
        if (!$key = $this->filterKeyForIds()) {
            return false;
        }

        return $filters->has($key);
    }

    /**
     * @param Collection $filters
     * @return array
     */
    protected function extractIds(Collection $filters)
    {
        $ids = $filters->get($this->filterKeyForIds());

        return $this->deserializeIdFilter($ids);
    }

    /**
     * Get the filter key that is used for a find-many query.
     *
     * @return string|null
     */
    protected function filterKeyForIds(): ?string
    {
        $key = property_exists($this, 'findManyFilter') ? $this->findManyFilter : null;

        return $key ?: DocumentInterface::KEYWORD_ID;
    }

    /**
     * Normalize the id filter.
     *
     * The id filter can either be a comma separated string of resource ids, or an
     * array of resource ids.
     *
     * @param array|string|null $resourceIds
     * @return array
     */
    protected function deserializeIdFilter($resourceIds): array
    {
        if (is_string($resourceIds)) {
            $resourceIds = explode(',', $resourceIds);
        }

        if (!is_array($resourceIds)) {
            throw new InvalidArgumentException('Expecting a string or array.');
        }

        return $this->databaseIds((array) $resourceIds);
    }

    /**
     * Convert resource ids to database ids.
     *
     * @param iterable $resourceIds
     * @return array
     */
    protected function databaseIds(iterable $resourceIds): array
    {
        return collect($resourceIds)->map(function ($resourceId) {
            return $this->databaseId($resourceId);
        })->all();
    }

    /**
     * Convert a resource id to a database id.
     *
     * Child classes can overload this method if they need to perform
     * any logic to convert a resource id to a database id.
     *
     * @param string $resourceId
     * @return mixed
     */
    protected function databaseId(string $resourceId)
    {
        return $resourceId;
    }
}
