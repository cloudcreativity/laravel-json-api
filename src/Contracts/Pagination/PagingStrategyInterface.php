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

namespace CloudCreativity\LaravelJsonApi\Contracts\Pagination;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * Interface PagingStrategyInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface PagingStrategyInterface
{

    /**
     * @param QueryBuilder|EloquentBuilder|Relation $query
     * @param QueryParametersInterface $parameters
     * @return PageInterface
     */
    public function paginate($query, QueryParametersInterface $parameters);

}
