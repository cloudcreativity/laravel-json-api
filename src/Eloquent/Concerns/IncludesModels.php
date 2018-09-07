<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

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
     * on this property. For example, if the JSON API `comments.created-by`
     * include path actually relates to `comments.user` model path, you can
     * define that mapping here:
     *
     * ```php
     * protected $includePaths = [
     *   'comments.created-by' => 'comments.user'
     * ];
     * ```
     *
     * It is also possible to map a single JSON API include path to
     * multiple model paths. For example:
     *
     * ```php
     * protected $includePaths = [
     *   'user' => ['user.city', 'user.organization']
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
     * Add eager loading to the query.
     *
     * @param Builder $query
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    protected function with($query, EncodingParametersInterface $parameters)
    {
        $query->with($this->getRelationshipPaths(
            (array) $parameters->getIncludePaths()
        ));
    }

    /**
     * Add eager loading to a record.
     *
     * @param Model $record
     * @param EncodingParametersInterface $parameters
     */
    protected function load($record, EncodingParametersInterface $parameters)
    {
        $relationshipPaths = $this->getRelationshipPaths($parameters->getIncludePaths());

        /** Eager load anything that needs to be loaded. */
        if (method_exists($record, 'loadMissing')) {
            $record->loadMissing($relationshipPaths);
        } else {
            /** @todo remove this when dropping support for Laravel 5.4 */
            $record->load($relationshipPaths);
        }
    }

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
        })->flatten()->filter()->values();
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
