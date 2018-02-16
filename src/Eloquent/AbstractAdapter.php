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

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use Carbon\Carbon;
use CloudCreativity\JsonApi\Adapter\AbstractResourceAdapter;
use CloudCreativity\JsonApi\Contracts\Adapter\HasManyAdapterInterface;
use CloudCreativity\JsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipsInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\JsonApi\Utils\Str;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PagingStrategyInterface;
use CloudCreativity\LaravelJsonApi\Store\FindsManyResources;
use CloudCreativity\Utils\Object\StandardObjectInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\SortParameterInterface;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters;

/**
 * Class EloquentAdapter
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractAdapter extends AbstractResourceAdapter
{

    use FindsManyResources,
        Concerns\IncludesModels;

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var PagingStrategyInterface|null
     */
    protected $paging;

    /**
     * The model key that is the primary key for the resource id.
     *
     * If empty, defaults to `Model::getKeyName()`.
     *
     * @var string|null
     */
    protected $primaryKey;

    /**
     * JSON API attribute field names to hydrate.
     *
     * - Empty array = hydrate no attributes.
     * - Non-empty array = hydrate the specified attribute keys (see below).
     * - Null = calculate the attributes to hydrate using `Model::getFillable()`
     *
     * List the keys from the resource's attributes that should be transferred to your
     * model using the `fill()` method. To map a resource attribute key to a different
     * model key, use a key/value pair where the key is the resource attribute and the
     * value is the model attribute.
     *
     * For example:
     *
     * ```
     * $attributes = [
     *  'foo',
     *  'bar' => 'baz'
     *  'foo-bar',
     * ];
     * ```
     *
     * Will transfer the `foo` resource attribute to the model `foo` attribute, and the
     * resource `bar` attribute to the model `baz` attribute. The `foo-bar` resource
     * attribute will be converted to `foo_bar` if the Model uses snake case attributes,
     * or `fooBar` if it does not use snake case.
     *
     * If this property is `null`, the attributes to hydrate will be calculated using
     * `Model::getFillable()`.
     *
     * @var array|null
     */
    protected $attributes = null;

    /**
     * JSON API relationship field names to hydrate.
     *
     * This hydrator can hydrate Eloquent `BelongsTo` and `BelongsToMany` relationships. To do so,
     * add the relationship name to this array. As per the attributes above, you can map
     * a resource relationship key to a different model key using a key/value pair. The model key
     * must be the method on the model to get the relationship object.
     *
     * @var string[]
     */
    protected $relationships = [];

    /**
     * The resource attributes that are dates.
     *
     * If an array, a list of JSON API resource attributes that should be cast to dates.
     * If `null`, the list will be calculated using `Model::getDates()`
     *
     * @var string[]|null
     */
    protected $dates = null;

    /**
     * The filter param for a find-many request.
     *
     * If null, defaults to the JSON API keyword `id`.
     *
     * @var string|null
     */
    protected $findManyFilter = null;

    /**
     * The default pagination to use if no page parameters have been provided.
     *
     * If your resource must always be paginated, use this to return the default
     * pagination variables... e.g. `['number' => 1]` for page 1.
     *
     * If this property is null or an empty array, then no pagination will be
     * used if no page parameters have been provided (i.e. every resource
     * will be returned).
     *
     * @var array|null
     */
    protected $defaultPagination = null;

    /**
     * The model relationships to eager load on every query.
     *
     * @var string[]|null
     * @deprecated use `$defaultWith` instead.
     */
    protected $with = null;

    /**
     * A mapping of sort parameters to columns.
     *
     * Use this to map any parameters to columns where the two are not identical. E.g. if
     * your sort param is called `sort` but the column to use is `type`, then set this
     * property to `['sort' => 'type']`.
     *
     * @var array
     */
    protected $sortColumns = [];

    /**
     * Apply the supplied filters to the builder instance.
     *
     * @param Builder $query
     * @param Collection $filters
     * @return void
     */
    abstract protected function filter(Builder $query, Collection $filters);

    /**
     * AbstractAdapter constructor.
     *
     * @param Model $model
     * @param PagingStrategyInterface|null $paging
     */
    public function __construct(Model $model, PagingStrategyInterface $paging = null)
    {
        $this->model = $model;
        $this->paging = $paging;

        if ($this->with) {
            $this->defaultWith = array_merge($this->defaultWith, $this->with);
        }
    }

    /**
     * @inheritDoc
     */
    public function query(EncodingParametersInterface $parameters)
    {
        $filters = $this->extractFilters($parameters);
        $query = $this->newQuery();

        /** Apply eager loading */
        $this->with($query, $this->extractIncludePaths($parameters));

        /** Find by ids */
        if ($this->isFindMany($filters)) {
            return $this->findByIds($query, $filters);
        }

        /** Filter and sort */
        $this->filter($query, $filters);
        $this->sort($query, (array) $parameters->getSortParameters());

        /** Return a single record if this is a search for one resource. */
        if ($this->isSearchOne($filters)) {
            return $this->first($query);
        }

        /** Paginate results if needed. */
        $pagination = $this->extractPagination($parameters);

        if (!$pagination->isEmpty() && !$this->hasPaging()) {
            throw new RuntimeException('Paging parameters exist but paging is not supported.');
        }

        return $pagination->isEmpty() ?
            $this->all($query) :
            $this->paginate($query, $this->normalizeParameters($parameters, $pagination));
    }

    /**
     * @inheritDoc
     */
    public function read($resourceId, EncodingParametersInterface $parameters)
    {
        if ($record = parent::read($resourceId, $parameters)) {
            $this->load($record, $parameters);
        }

        return $record;
    }

    /**
     * @inheritdoc
     */
    public function update($record, ResourceObjectInterface $resource, EncodingParametersInterface $parameters)
    {
        /** @var Model $record */
        $record = parent::update($record, $resource, $parameters);
        $relationshipPaths = $this->getRelationshipPaths($this->extractIncludePaths($parameters));

        /** Eager load anything that needs to be loaded. */
        if (method_exists($record, 'loadMissing')) {
            $record->loadMissing($relationshipPaths);
        } else {
            /** @todo remove this when dropping support for Laravel 5.4 */
            $record->load($relationshipPaths);
        }

        return $record;
    }

    /**
     * @inheritdoc
     */
    public function delete($record, EncodingParametersInterface $params)
    {
        /** @var Model $record */
        return (bool) $record->delete();
    }

    /**
     * @inheritDoc
     */
    public function exists($resourceId)
    {
        return $this->newQuery()->where($this->getQualifiedKeyName(), $resourceId)->exists();
    }

    /**
     * @inheritDoc
     */
    public function find($resourceId)
    {
        return $this->newQuery()->where($this->getQualifiedKeyName(), $resourceId)->first();
    }

    /**
     * @inheritDoc
     */
    public function findMany(array $resourceIds)
    {
        return $this->newQuery()->whereIn($this->getQualifiedKeyName(), $resourceIds)->get()->all();
    }

    /**
     * @inheritDoc
     */
    public function related($relationshipName)
    {
        $method = Str::camelize($relationshipName);
        $relation = method_exists($this, $method) ? $this->{$method}() : null;

        if (!$relation instanceof RelationshipAdapterInterface) {
            throw new RuntimeException("Unrecognised relationship name: $method");
        }

        if ($relation instanceof StoreAwareInterface) {
            $relation->withStore($this->store());
        }

        if (method_exists($relation, 'withRelationshipName')) {
            $relation->withRelationshipName($relationshipName);
        }

        return $relation;
    }

    /**
     * Get a new query builder.
     *
     * Child classes can overload this method if they want to modify the query instance that
     * is used for every query the adapter does.
     *
     * @return Builder
     */
    protected function newQuery()
    {
        return $this->model->newQuery();
    }

    /**
     * Add eager loading to the query.
     *
     * @param Builder $query
     * @param Collection $includePaths
     *      the paths for resources that will be included.
     * @return void
     */
    protected function with(Builder $query, Collection $includePaths)
    {
        $query->with($this->getRelationshipPaths($includePaths));
    }

    /**
     * Add eager loading to a record.
     *
     * @param $record
     * @param EncodingParametersInterface $parameters
     */
    protected function load($record, EncodingParametersInterface $parameters)
    {
        $relationshipPaths = $this->getRelationshipPaths($this->extractIncludePaths($parameters));

        /** Eager load anything that needs to be loaded. */
        if (method_exists($record, 'loadMissing')) {
            $record->loadMissing($relationshipPaths);
        } else {
            /** @todo remove this when dropping support for Laravel 5.4 */
            $record->load($relationshipPaths);
        }
    }

    /**
     * @inheritDoc
     */
    protected function createRecord(ResourceObjectInterface $resource)
    {
        return $this->model->newInstance();
    }

    /**
     * @param StandardObjectInterface $attributes
     *      the attributes received from the client.
     * @param Model $record
     *      the model being hydrated
     * @return array
     *      the JSON API attribute keys to hydrate
     * @deprecated use `hydrateAttributeFields`
     */
    protected function attributeKeys(StandardObjectInterface $attributes, $record)
    {
        return $this->hydrateAttributeFields($record);
    }

    /**
     * Get the JSON API attribute fields that can be hydrated.
     *
     * @param Model $model
     *      the model being hydrated.
     * @return array
     */
    protected function hydrateAttributeFields($model)
    {
       if (is_array($this->attributes)) {
           return $this->attributes;
       }

        return $this->attributes = collect($model->getFillable())->mapWithKeys(function ($modelKey) {
            return [$this->fieldForModelKey($modelKey) => $modelKey];
        })->all();
    }

    /**
     * Get the JSON API relationship fields that can be hydrated.
     *
     * @param Model $model
     *      the model being hydrated.
     * @return array
     */
    protected function hydrateRelationshipFields($model)
    {
        return (array) $this->relationships;
    }

    /**
     * @inheritDoc
     */
    protected function hydrateAttributes($record, StandardObjectInterface $attributes)
    {
        if (!$record instanceof Model) {
            throw new \InvalidArgumentException('Expecting an Eloquent model.');
        }

        $data = [];

        foreach ($this->attributeKeys($attributes, $record) as $resourceKey => $modelKey) {
            if (is_numeric($resourceKey)) {
                $resourceKey = $modelKey;
                $modelKey = $this->keyForAttribute($modelKey, $record);
            }

            if ($attributes->has($resourceKey)) {
                $data[$modelKey] = $this->deserializeAttribute($attributes->get($resourceKey), $resourceKey, $record);
            }
        }

        $record->fill($data);
    }

    /**
     * Convert a JSON API attribute key into a model attribute key.
     *
     * @param $resourceKey
     * @param Model $model
     * @return string
     * @deprecated use `modelKeyForField`
     */
    protected function keyForAttribute($resourceKey, Model $model)
    {
        return $this->modelKeyForField($resourceKey);
    }

    /**
     * Convert a JSON API resource field name to a model key.
     *
     * @param $field
     * @return string
     */
    protected function modelKeyForField($field)
    {
        $model = $this->model;

        return $model::$snakeAttributes ? Str::underscore($field) : Str::camelize($field);
    }

    /**
     * Convert a model key to a JSON API resource field name.
     *
     * @param $key
     * @return string
     */
    protected function fieldForModelKey($key)
    {
        return Str::dasherize($key);
    }

    /**
     * Deserialize a value obtained from the resource's attributes.
     *
     * @param $value
     *      the value that the client provided.
     * @param $resourceKey
     *      the attribute key for the value
     * @param Model $record
     * @return Carbon|null
     */
    protected function deserializeAttribute($value, $resourceKey, $record)
    {
        if ($this->isDateAttribute($resourceKey, $record)) {
            return $this->deserializeDate($value);
        }

        return $value;
    }

    /**
     * Convert a JSON date into a PHP date time object.
     *
     * @param $value
     * @return Carbon|null
     */
    protected function deserializeDate($value)
    {
        return !is_null($value) ? new Carbon($value) : null;
    }

    /**
     * Is this resource key a date attribute?
     *
     * @param $resourceKey
     * @param Model $record
     * @return bool
     */
    protected function isDateAttribute($resourceKey, $record)
    {
        if (is_null($this->dates)) {
            return in_array(Str::underscore($resourceKey), $record->getDates(), true);
        }

        return in_array($resourceKey, $this->dates, true);
    }

    /**
     * @inheritDoc
     */
    protected function hydrateRelationships(
        $record,
        RelationshipsInterface $relationships,
        EncodingParametersInterface $parameters
    ) {
        foreach ($this->hydrateRelationshipFields($record) as $field) {
            if (!$relationships->has($field)) {
                continue;
            }

            $relation = $this->related($field);

            if (!$this->requiresPrimaryRecordPersistence($relation)) {
                $relation->update($record, $relationships->getRelationship($field), $parameters);
            }
        }
    }

    /**
     * Hydrate related models after the primary record has been persisted.
     *
     * @param Model $record
     * @param ResourceObjectInterface $resource
     * @param EncodingParametersInterface $parameters
     */
    protected function hydrateRelated(
        $record,
        ResourceObjectInterface $resource,
        EncodingParametersInterface $parameters
    ) {
        $relationships = $resource->getRelationships();
        $changed = false;

        foreach ($this->hydrateRelationshipFields($record) as $field) {
            if (!$relationships->has($field)) {
                continue;
            }

            $relation = $this->related($field);

            if ($this->requiresPrimaryRecordPersistence($relation)) {
                $relation->update($record, $relationships->getRelationship($field), $parameters);
                $changed = true;
            }
        }

        /** If there are changes, we need to refresh the model in-case the relationship has been cached. */
        if ($changed) {
            $record->refresh();
        }
    }

    /**
     * Does the relationship need to be hydrated after the primary record has been persisted?
     *
     * @param RelationshipAdapterInterface $relation
     * @return bool
     */
    protected function requiresPrimaryRecordPersistence(RelationshipAdapterInterface $relation)
    {
        return $relation instanceof HasManyAdapterInterface || $relation instanceof HasOne;
    }

    /**
     * @param Model $record
     * @return Model
     */
    protected function persist($record)
    {
        $record->save();

        return $record;
    }

    /**
     * @param Builder $query
     * @param Collection $filters
     * @return mixed
     */
    protected function findByIds(Builder $query, Collection $filters)
    {
        return $query
            ->whereIn($this->getQualifiedKeyName(), $this->extractIds($filters))
            ->get();
    }

    /**
     * Return the result for a search one query.
     *
     * @param Builder $query
     * @return Model
     */
    protected function first(Builder $query)
    {
        return $query->first();
    }

    /**
     * Return the result for query that is not paginated.
     *
     * @param Builder $query
     * @return mixed
     */
    protected function all(Builder $query)
    {
        return $query->get();
    }

    /**
     * Is this a search for a singleton resource?
     *
     * @param Collection $filters
     * @return bool
     */
    protected function isSearchOne(Collection $filters)
    {
        return false;
    }

    /**
     * Return the result for a paginated query.
     *
     * @param Builder $query
     * @param EncodingParametersInterface $parameters
     * @return PageInterface
     */
    protected function paginate(Builder $query, EncodingParametersInterface $parameters)
    {
        return $this->paging->paginate($query, $parameters);
    }

    /**
     * Get the key that is used for the resource ID.
     *
     * @return string
     */
    protected function getKeyName()
    {
        return $this->primaryKey ?: $this->model->getKeyName();
    }

    /**
     * @return string
     */
    protected function getQualifiedKeyName()
    {
        return sprintf('%s.%s', $this->model->getTable(), $this->getKeyName());
    }

    /**
     * @param EncodingParametersInterface $parameters
     * @return Collection
     */
    protected function extractIncludePaths(EncodingParametersInterface $parameters)
    {
        return collect($parameters->getIncludePaths());
    }

    /**
     * @param EncodingParametersInterface $parameters
     * @return Collection
     */
    protected function extractFilters(EncodingParametersInterface $parameters)
    {
        return collect($parameters->getFilteringParameters());
    }

    /**
     * @param EncodingParametersInterface $parameters
     * @return Collection
     */
    protected function extractPagination(EncodingParametersInterface $parameters)
    {
        $pagination = (array) $parameters->getPaginationParameters();

        return collect($pagination ?: $this->defaultPagination());
    }

    /**
     * @return array
     */
    protected function defaultPagination()
    {
        return (array) $this->defaultPagination;
    }

    /**
     * @return bool
     */
    protected function hasPaging()
    {
        return $this->paging instanceof PagingStrategyInterface;
    }

    /**
     * Apply sort parameters to the query.
     *
     * @param Builder $query
     * @param SortParameterInterface[] $sortBy
     * @return void
     */
    protected function sort(Builder $query, array $sortBy)
    {
        if (empty($sortBy)) {
            $this->defaultSort($query);
            return;
        }

        /** @var SortParameterInterface $param */
        foreach ($sortBy as $param) {
            $this->sortBy($query, $param);
        }
    }

    /**
     * Apply a default sort order if the client has not requested any sort order.
     *
     * Child classes can override this method if they want to implement their
     * own default sort order.
     *
     * @param Builder $query
     * @return void
     */
    protected function defaultSort(Builder $query)
    {
    }

    /**
     * @param Builder $query
     * @param SortParameterInterface $param
     */
    protected function sortBy(Builder $query, SortParameterInterface $param)
    {
        $column = $this->getQualifiedSortColumn($query, $param->getField());
        $order = $param->isAscending() ? 'asc' : 'desc';

        $query->orderBy($column, $order);
    }

    /**
     * @param Builder $query
     * @param string $field
     * @return string
     */
    protected function getQualifiedSortColumn(Builder $query, $field)
    {
        $key = $this->columnForField($field, $query->getModel());

        if (!str_contains($key, '.')) {
            $key = sprintf('%s.%s', $query->getModel()->getTable(), $key);
        }

        return $key;
    }

    /**
     * Get the table column to use for the specified search field.
     *
     * @param string $field
     * @param Model $model
     * @return string
     */
    protected function columnForField($field, Model $model)
    {
        /** If there is a custom mapping, return that */
        if (isset($this->sortColumns[$field])) {
            return $this->sortColumns[$field];
        }

        return $model::$snakeAttributes ? Str::underscore($field) : Str::camelize($field);
    }

    /**
     * @param string|null $modelKey
     * @return BelongsTo
     */
    protected function belongsTo($modelKey = null)
    {
        return new BelongsTo($this->model, $modelKey ?: $this->guessRelation());
    }

    /**
     * @param string|null $modelKey
     * @return HasOne
     */
    protected function hasOne($modelKey = null)
    {
        return new HasOne($this->model, $modelKey ?: $this->guessRelation());
    }

    /**
     * @param string|null $modelKey
     * @return HasMany
     */
    protected function hasMany($modelKey = null)
    {
        return new HasMany($this->model, $modelKey ?: $this->guessRelation());
    }

    /**
     * @param HasManyAdapterInterface[] ...$adapters
     * @return MorphHasMany
     */
    protected function morphMany(HasManyAdapterInterface ...$adapters)
    {
        return new MorphHasMany(...$adapters);
    }

    /**
     * @return string
     */
    private function guessRelation()
    {
        list($one, $two, $caller) = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $caller['function'];
    }

    /**
     * Normalize parameters for pagination.
     *
     * This is a temporary solution for Issue #131 in the v0.11/v0.12 series.
     *
     * @param EncodingParametersInterface $parameters
     * @param Collection $extractedPagination
     * @return EncodingParameters
     * @see https://github.com/cloudcreativity/laravel-json-api/issues/131
     */
    private function normalizeParameters(EncodingParametersInterface $parameters, Collection $extractedPagination)
    {
        return new EncodingParameters(
            $parameters->getIncludePaths(),
            $parameters->getFieldSets(),
            $parameters->getSortParameters(),
            $extractedPagination->all(),
            $parameters->getFilteringParameters(),
            $parameters->getUnrecognizedParameters()
        );
    }

}
