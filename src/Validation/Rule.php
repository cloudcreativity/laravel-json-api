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

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Rules\DateTimeIso8601;
use CloudCreativity\LaravelJsonApi\Rules\HasMany;
use CloudCreativity\LaravelJsonApi\Rules\HasOne;

class Rule
{

    /**
     * Get a rule for an ISO 8601 compliant date time.
     *
     * @return DateTimeIso8601
     */
    public static function dateTime()
    {
        return new DateTimeIso8601();
    }

    /**
     * Get a rule for a to-many relationship.
     *
     * @param string ...$types
     * @return HasMany
     */
    public static function hasMany(string ...$types): HasMany
    {
        return new HasMany(...$types);
    }

    /**
     * Get a rule for a to-one relationship.
     *
     * @param string ...$types
     * @return HasOne
     */
    public static function hasOne(string ...$types): HasOne
    {
        return new HasOne(...$types);
    }
}
