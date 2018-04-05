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

namespace CloudCreativity\JsonApi\Utils;

/**
 * Class Str
 *
 * @package CloudCreativity\JsonApi
 */
class Str
{

    /**
     * @var array
     */
    private static $dasherized = [];

    /**
     * @var array
     */
    private static $decamelized = [];

    /**
     * @var array
     */
    private static $underscored = [];

    /**
     * @var array
     */
    private static $camelized = [];

    /**
     * @var array
     */
    private static $classified = [];

    /**
     * Replaces underscores or camel case with dashes.
     *
     * @param string $value
     * @return string
     */
    public static function dasherize($value)
    {
        if (isset(self::$dasherized[$value])) {
            return self::$dasherized[$value];
        }

        return self::$dasherized[$value] = str_replace('_', '-', self::decamelize($value));
    }

    /**
     * Converts a camel case string into all lower case separated by underscores.
     *
     * @param string $value
     * @return string
     */
    public static function decamelize($value)
    {
        if (isset(self::$decamelized[$value])) {
            return self::$decamelized[$value];
        }

        return self::$decamelized[$value] = strtolower(preg_replace('/(.)(?=[A-Z])/u', '$1_', $value));
    }

    /**
     * Converts a camel case or dasherized string into a lower cased and underscored string.
     *
     * @param $value
     * @return string
     */
    public static function underscore($value)
    {
        if (isset(self::$underscored[$value])) {
            return self::$underscored[$value];
        }

        return self::$underscored[$value] = str_replace('-', '_', self::decamelize($value));
    }

    /**
     * Gets the lower camel case form of a string.
     *
     * @param string $value
     * @return string
     */
    public static function camelize($value)
    {
        if (isset(self::$camelized[$value])) {
            return self::$camelized[$value];
        }

        return self::$camelized[$value] = lcfirst(self::classify($value));
    }

    /**
     * Gets the upper camel case form of a string.
     *
     * @param string $value
     * @return string
     */
    public static function classify($value)
    {
        if (isset(self::$classified[$value])) {
            return self::$classified[$value];
        }

        $converted = ucwords(str_replace(['-', '_'], ' ', $value));

        return self::$classified[$value] = str_replace(' ', '', $converted);
    }
}
