<?php

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
}
