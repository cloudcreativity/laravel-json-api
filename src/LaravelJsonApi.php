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

namespace CloudCreativity\LaravelJsonApi;

class LaravelJsonApi
{

    /**
     * The default API name.
     *
     * @var null
     */
    public static $defaultApi = 'default';

    /**
     * Indicates if Laravel JSON API migrations will be run.
     *
     * @var bool
     */
    public static $runMigrations = false;

    /**
     * Indicates if listeners will be bound to the Laravel queue events.
     *
     * @var bool
     */
    public static $queueBindings = true;

    /**
     * Indicates if Laravel validator failed data is added to JSON API error objects.
     *
     * @var bool
     */
    public static $validationFailures = false;

    /**
     * Set the default API name.
     *
     * @param string $name
     * @return LaravelJsonApi
     */
    public static function defaultApi(string $name): self
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Default API name must not be empty.');
        }

        self::$defaultApi = $name;

        return new self();
    }

    /**
     * @return LaravelJsonApi
     */
    public static function runMigrations(): self
    {
        self::$runMigrations = true;

        return new self();
    }

    /**
     * @return LaravelJsonApi
     */
    public static function skipQueueBindings(): self
    {
        self::$queueBindings = false;

        return new self();
    }

    /**
     * @return LaravelJsonApi
     */
    public static function showValidatorFailures(): self
    {
        self::$validationFailures = true;

        return new self();
    }
}
