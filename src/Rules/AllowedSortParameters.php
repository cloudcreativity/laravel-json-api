<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Rules;

use Illuminate\Support\Collection;

/**
 * Class AllowedSortParameters
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class AllowedSortParameters extends AbstractAllowedRule
{

    /**
     * @inheritDoc
     */
    protected function extract($value): Collection
    {
        $params = is_string($value) ? explode(',', $value) : [];

        return collect($params)->map(function ($param) {
            return ltrim($param, '+-');
        })->unique()->values();
    }

}
