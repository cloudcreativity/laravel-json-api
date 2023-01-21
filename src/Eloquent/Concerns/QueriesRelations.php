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

namespace CloudCreativity\LaravelJsonApi\Eloquent\Concerns;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Trait QueriesRelations
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait QueriesRelations
{

    /**
     * Get the relation from the model.
     *
     * @param Model $record
     * @param string $key
     * @return Relation|Builder
     */
    protected function getRelation($record, $key)
    {
        $relation = $record->{$key}();

        if (!$relation || !$this->acceptRelation($relation)) {
            throw new RuntimeException(sprintf(
                'JSON API relation %s cannot be used for an Eloquent %s relation.',
                class_basename($this),
                class_basename($relation)
            ));
        }

        return $relation;
    }

    /**
     * Is the supplied Eloquent relation acceptable for this JSON API relation?
     *
     * @param Relation $relation
     * @return bool
     */
    protected function acceptRelation($relation)
    {
        return $relation instanceof Relation;
    }

    /**
     * Does the query need to be passed to the inverse adapter?
     *
     * @param $record
     * @param QueryParametersInterface $parameters
     * @return bool
     */
    protected function requiresInverseAdapter($record, QueryParametersInterface $parameters)
    {
        return !empty($parameters->getFilteringParameters()) ||
            !empty($parameters->getSortParameters()) ||
            !empty($parameters->getPaginationParameters()) ||
            !empty($parameters->getIncludePaths());
    }

    /**
     * Get an Eloquent adapter for the supplied record's relationship.
     *
     * @param Relation|Builder $relation
     * @return AbstractAdapter
     */
    protected function adapterFor($relation)
    {
        $adapter = $this->getStore()->adapterFor($relation->getModel());

        if (!$adapter instanceof AbstractAdapter) {
            throw new RuntimeException('Expecting inverse resource adapter to be an Eloquent adapter.');
        }

        return $adapter;
    }
}
