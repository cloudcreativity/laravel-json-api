<?php

namespace CloudCreativity\LaravelJsonApi\Eloquent\Concerns;

use CloudCreativity\JsonApi\Utils\Str;
use Illuminate\Support\Collection;

trait IncludesModels
{

    /**
     * The model relationships to eager load on every query.
     *
     * @var string[]
     */
    protected $defaultWith = [];

    /**
     * Mapping of JSON API include paths to model relationship paths.
     *
     * This adapter automatically maps include paths to model eager load
     * relationships. For example, if the JSON API include path is
     * `comments.created-by`, the model relationship `comments.createdBy`
     * will be eager loaded.
     *
     * If there are any paths that do not map directly, you can define them
     * on this property. For instance, if the JSON API `comments.created-by`
     * path actually relates to `comments.user` model path, you can
     * define that mapping here:
     *
     * ```php
     * protected $includePaths = [
     *   'comments.author' => 'comments.user'
     * ];
     * ```
     *
     * To prevent an include path from being eager loaded, set its value
     * to `null` in the map. E.g.
     *
     * ```php
     * protected $includePaths = [
     *   'comments.author' => null,
     * ];
     * ```
     *
     * @var array
     */
    protected $includePaths = [];

    /**
     * Get the relationship paths to eager load.
     *
     * @param Collection|array $includePaths
     *      the JSON API resource paths to be included.
     * @return array
     */
    protected function getRelationshipPaths($includePaths)
    {
        return $this
            ->convertIncludePaths($includePaths)
            ->merge($this->defaultWith)
            ->unique()
            ->all();
    }

    /**
     * @param Collection|array $includePaths
     * @return Collection
     */
    protected function convertIncludePaths($includePaths)
    {
        return collect($includePaths)->map(function ($path) {
            return $this->convertIncludePath($path);
        })->filter()->values();
    }

    /**
     * Convert a JSON API include path to a model relationship path.
     *
     * @param $path
     * @return string|null
     */
    protected function convertIncludePath($path)
    {
        if (array_key_exists($path, $this->includePaths)) {
            return $this->includePaths[$path] ?: null;
        }

        return collect(explode('.', $path))->map(function ($segment) {
            return Str::camelize($segment);
        })->implode('.');
    }
}
