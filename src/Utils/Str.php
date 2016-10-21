<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Utils;

use Illuminate\Support\Str as IlluminateStr;

/**
 * Class Str
 * @package CloudCreativity\LaravelJsonApi
 */
final class Str
{

    /**
     * Dasherize a string
     *
     * The JSON API spec recommends using hyphens for member names. This method
     * converts snake case or camel case strings to their hyphenated equivalent.
     *
     * @param $value
     * @return string
     */
    public static function dasherize($value)
    {
        return self::snake($value, '-');
    }

    /**
     * Snake case a dasherized string
     *
     * @param $value
     * @param string $delimiter
     * @return string
     */
    public static function snake($value, $delimiter = '_')
    {
        $value = IlluminateStr::camel($value);

        return IlluminateStr::snake($value, $delimiter);
    }

    /**
     * Camel case a dasherized string
     *
     * @param $value
     * @return string
     */
    public static function camel($value)
    {
        return IlluminateStr::camel($value);
    }
}
