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

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\LaravelJsonApi\Adapter\AbstractResourceAdapter;
use CloudCreativity\LaravelJsonApi\Contracts\Adapter\HasManyAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerAwareInterface;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PagingStrategyInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Schema\SchemaProviderInterface;
use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use CloudCreativity\LaravelJsonApi\Http\Query\QueryParameters;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Collection;

/**
 * Class AbstractAdapter
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractAdapter extends AbstractResourceAdapter implements ContainerAwareInterface
{

    use Concerns\DeserializesAttributes,
        Concerns\IncludesModels,
        Concerns\SortsModels,
        Concerns\FiltersModels;

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
     * If empty, defaults to `Model::getRouteKeyName()`.
     *
     * @var string|null
     */
    protected $primaryKey;

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
     * @var array
     */
    private $scopes;

    /**
     * @var SchemaProviderInterface|null
     */
    private ?SchemaProviderInterface $schema = null;

    /**
     * Apply the supplied filters to the builder instance.
     *
     * @param Builder $query
     * @param Collection $filters
     * @return void
     */
    abstract protected function filter($query, Collection $filters);

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
        $this->scopes = [];
    }

    /**
     * @inheritDoc
     */
    public function withContainer(ContainerInterface $container): void
    {
        $this->schema = $container->getSchema($this->model);
    }

    /**
     * @inheritDoc
     */
    public function query(QueryParametersInterface $parameters)
    {
        $parameters = $this->getQueryParameters($parameters);

        return $this->queryAllOrOne($this->newQuery(), $parameters);
    }

    /**
     * Query the resource when it appears in a to-many relation of a parent resource.
     *
     * For example, a request to `/posts/1/comments` will invoke this method on the
     * comments adapter.
     *
     * @param Relations\BelongsToMany|Relations\HasMany|Relations\HasManyThrough|Builder $relation
     * @param QueryParametersInterface $parameters
     * @return mixed
     * @todo default pagination causes a problem with polymorphic relations??
     */
    public function queryToMany($relation, QueryParametersInterface $parameters)
    {
        $this->applyScopes(
            $query = $this->newRelationQuery($relation)
        );

        return $this->queryAllOrOne(
            $query,
            $this->getQueryParameters($parameters)
        );
    }

    /**
     * Query the resource when it appears in a to-one relation of a parent resource.
     *
     * For example, a request to `/posts/1/author` will invoke this method on the
     * user adapter when the author relation returns a `users` resource.
     *
     * @param Relations\BelongsTo|Relations\HasOne|Builder $relation
     * @param QueryParametersInterface $parameters
     * @return mixed
     */
    public function queryToOne($relation, QueryParametersInterface $parameters)
    {
        $this->applyScopes(
            $query = $this->newRelationQuery($relation)
        );

        return $this->queryOne(
            $query,
            $this->getQueryParameters($parameters)
        );
    }

    /**
     * @inheritDoc
     */
    public function read($record, QueryParametersInterface $parameters)
    {
        $parameters = $this->getQueryParameters($parameters);

        if (!empty($parameters->getFilteringParameters())) {
            $record = $this->readWithFilters($record, $parameters);
        }

        if ($record) {
            $this->load($record, $parameters);
        }

        return $record;
    }

    /**
     * @inheritdoc
     */
    public function update($record, array $document, QueryParametersInterface $parameters)
    {
        $parameters = $this->getQueryParameters($parameters);

        /** @var Model $record */
        $record = parent::update($record, $document, $parameters);
        $this->load($record, $parameters);

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function exists(string $resourceId): bool
    {
        return $this->findQuery($resourceId)->exists();
    }

    /**
     * @inheritDoc
     */
    public function find(string $resourceId)
    {
        return $this->findQuery($resourceId)->first();
    }

    /**
     * @inheritDoc
     */
    public function findMany(iterable $resourceIds): iterable
    {
        return $this->findManyQuery($resourceIds)->get()->all();
    }

    /**
     * Add scopes.
     *
     * @param Scope ...$scopes
     * @return $this
     */
    public function addScopes(Scope ...$scopes): self
    {
        foreach ($scopes as $scope) {
            $this->scopes[get_class($scope)] = $scope;
        }

        return $this;
    }

    /**
     * Add a global scope using a closure.
     *
     * @param \Closure $scope
     * @param string|null $identifier
     * @return $this
     */
    public function addClosureScope(\Closure $scope, string $identifier = null): self
    {
        $identifier = $identifier ?: spl_object_hash($scope);

        $this->scopes[$identifier] = $scope;

        return $this;
    }

    /**
     * @param Builder $query
     * @return void
     */
    protected function applyScopes($query): void
    {
        /** @var Scope $scope */
        foreach ($this->scopes as $identifier => $scope) {
            $query->withGlobalScope($identifier, $scope);
        }
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
        $this->applyScopes(
            $builder = $this->model->newQuery()
        );

        return $builder;
    }

    /**
     * @param Relations\BelongsToMany|Relations\HasMany|Relations\HasManyThrough|Builder $relation
     * @return Builder
     */
    protected function newRelationQuery($relation)
    {
        return $relation->newQuery();
    }

    /**
     * @param $resourceId
     * @return Builder
     */
    protected function findQuery($resourceId)
    {
        return $this->newQuery()->where(
            $this->getQualifiedKeyName(),
            $this->databaseId($resourceId)
        );
    }

    /**
     * @param iterable $resourceIds
     * @return Builder
     */
    protected function findManyQuery(iterable $resourceIds)
    {
        return $this->newQuery()->whereIn(
            $this->getQualifiedKeyName(),
            $this->databaseIds($resourceIds)
        );
    }

    /**
     * Does the record match the supplied filters?
     *
     * @param Model $record
     * @param QueryParametersInterface $parameters
     * @return Model|null
     */
    protected function readWithFilters($record, QueryParametersInterface $parameters)
    {
        $query = $this->newQuery()->whereKey($record->getKey());
        $this->applyFilters($query, collect($parameters->getFilteringParameters()));

        return $query->exists() ? $record : null;
    }

    /**
     * Apply filters to the provided query parameter.
     *
     * @param Builder $query
     * @param Collection $filters
     */
    protected function applyFilters($query, Collection $filters)
    {
        /**
         * By default we support the `id` filter. If we use the filter,
         * we remove it so that it is not re-used by the `filter` method.
         */
        if ($this->isFindMany($filters)) {
            $this->filterByIds($query, $filters);
        }

        /** Hook for custom filters. */
        $this->filter($query, $filters);
    }

    /**
     * @inheritDoc
     */
    protected function createRecord(ResourceObject $resource)
    {
        return $this->model->newInstance();
    }

    /**
     * @inheritDoc
     */
    protected function destroy($record)
    {
        /** @var Model $record */
        return (bool) $record->delete();
    }

    /**
     * @inheritdoc
     */
    protected function fillRelationship(
        $record,
        $field,
        array $relationship,
        QueryParametersInterface $parameters
    ) {
        $relation = $this->getRelated($field);

        if (!$this->requiresPrimaryRecordPersistence($relation)) {
            $relation->update($record, $relationship, $parameters);
        }
    }

    /**
     * Hydrate related models after the primary record has been persisted.
     *
     * @param Model $record
     * @param ResourceObject $resource
     * @param QueryParametersInterface $parameters
     */
    protected function fillRelated(
        $record,
        ResourceObject $resource,
        QueryParametersInterface $parameters
    ) {
        $relationships = $resource->getRelationships();
        $changed = false;

        foreach ($relationships as $field => $value) {
            /** Skip any fields that are not fillable. */
            if ($this->isNotFillable($field, $record)) {
                continue;
            }

            /** Skip any fields that are not relations */
            if (!$this->isRelation($field)) {
                continue;
            }

            $relation = $this->getRelated($field);

            if ($this->requiresPrimaryRecordPersistence($relation)) {
                $relation->update($record, $value, $parameters);
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
     * @inheritdoc
     */
    protected function persist($record)
    {
        $record->save();
    }

    /**
     * @param $query
     * @param Collection $filters
     * @return void
     */
    protected function filterByIds($query, Collection $filters)
    {
        $query->whereIn(
            $this->getQualifiedKeyName(),
            $this->extractIds($filters)
        );
    }

    /**
     * Return the result for a search one query.
     *
     * @param Builder $query
     * @return Model
     */
    protected function searchOne($query)
    {
        return $query->first();
    }

    /**
     * Return the result for query that is not paginated.
     *
     * @param Builder $query
     * @return mixed
     */
    protected function searchAll($query)
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
     * @param QueryParametersInterface $parameters
     * @return PageInterface
     */
    protected function paginate($query, QueryParametersInterface $parameters)
    {
        if (!$this->paging) {
            throw new RuntimeException('Paging is not supported on adapter: ' . get_class($this));
        }

        /**
         * Set the key name on the strategy, so it knows what column is being used
         * for the resource's ID.
         *
         * @todo 2.0 add `withQualifiedKeyName` to the paging strategy interface.
         */
        if (method_exists($this->paging, 'withQualifiedKeyName')) {
            $this->paging->withQualifiedKeyName($this->getQualifiedKeyName());
        }

        return $this->paging->paginate($query, $parameters);
    }

    /**
     * Get the key that is used for the resource ID.
     *
     * @return string
     */
    protected function getKeyName()
    {
        return $this->primaryKey ?: $this->model->getRouteKeyName();
    }

    /**
     * @return string
     */
    protected function getQualifiedKeyName()
    {
        return $this->model->qualifyColumn($this->getKeyName());
    }

    /**
     * Get pagination parameters to use when the client has not provided paging parameters.
     *
     * @return array
     */
    protected function defaultPagination()
    {
        return (array) $this->defaultPagination;
    }

    /**
     * @param string|null $modelKey
     * @return BelongsTo
     */
    protected function belongsTo($modelKey = null)
    {
        return new BelongsTo($modelKey ?: $this->guessRelation());
    }

    /**
     * @param string|null $modelKey
     * @return HasOne
     */
    protected function hasOne($modelKey = null)
    {
        return new HasOne($modelKey ?: $this->guessRelation());
    }

    /**
     * @param string|null $modelKey
     * @return HasOneThrough
     */
    protected function hasOneThrough($modelKey = null)
    {
        return new HasOneThrough($modelKey ?: $this->guessRelation());
    }

    /**
     * @param string|null $modelKey
     * @return HasMany
     */
    protected function hasMany($modelKey = null)
    {
        return new HasMany($modelKey ?: $this->guessRelation());
    }

    /**
     * @param string|null $modelKey
     * @return HasManyThrough
     */
    protected function hasManyThrough($modelKey = null)
    {
        return new HasManyThrough($modelKey ?: $this->guessRelation());
    }

    /**
     * @param HasManyAdapterInterface ...$adapters
     * @return MorphHasMany
     */
    protected function morphMany(HasManyAdapterInterface ...$adapters)
    {
        return new MorphHasMany(...$adapters);
    }

    /**
     * @param \Closure $factory
     *      a factory that creates a new Eloquent query builder.
     * @return QueriesMany
     */
    protected function queriesMany(\Closure $factory)
    {
        return new QueriesMany($factory);
    }

    /**
     * @param \Closure $factory
     * @return QueriesOne
     */
    protected function queriesOne(\Closure $factory)
    {
        return new QueriesOne($factory);
    }

    /**
     * Default query execution used when querying records or relations.
     *
     * @param $query
     * @param QueryParametersInterface $parameters
     * @return mixed
     */
    protected function queryAllOrOne($query, QueryParametersInterface $parameters)
    {
        $filters = collect($parameters->getFilteringParameters());

        if ($this->isSearchOne($filters)) {
            return $this->queryOne($query, $parameters);
        }

        return $this->queryAll($query, $parameters);
    }

    /**
     * @param $query
     * @param QueryParametersInterface $parameters
     * @return PageInterface|mixed
     */
    protected function queryAll($query, QueryParametersInterface $parameters)
    {
        /** Apply eager loading */
        $this->with($query, $parameters);

        /** Filter */
        $this->applyFilters($query, collect($parameters->getFilteringParameters()));

        /** Sort */
        $this->sort($query, $parameters->getSortParameters());

        /** Paginate results if needed. */
        $pagination = collect($parameters->getPaginationParameters());

        return $pagination->isEmpty() ?
            $this->searchAll($query) :
            $this->paginate($query, $parameters);
    }

    /**
     * @param $query
     * @param QueryParametersInterface $parameters
     * @return Model
     */
    protected function queryOne($query, QueryParametersInterface $parameters)
    {
        $parameters = $this->getQueryParameters($parameters);

        /** Apply eager loading */
        $this->with($query, $parameters);

        /** Filter */
        $this->applyFilters($query, collect($parameters->getFilteringParameters()));

        /** Sort */
        $this->sort($query, $parameters->getSortParameters());

        return $this->searchOne($query);
    }

    /**
     * Get JSON:API parameters to use when constructing an Eloquent query.
     *
     * This method is used to push in any default parameter values that should
     * be used if the client has not provided any.
     *
     * @param QueryParametersInterface $parameters
     * @return QueryParametersInterface
     */
    protected function getQueryParameters(QueryParametersInterface $parameters)
    {
        return new QueryParameters(
            $parameters->getIncludePaths() ?? $this->getSchema()->getIncludePaths(),
            $parameters->getFieldSets(),
            $parameters->getSortParameters() ?: $this->defaultSort(),
            $parameters->getPaginationParameters() ?: $this->defaultPagination(),
            $parameters->getFilteringParameters(),
            $parameters->getUnrecognizedParameters()
        );
    }

    /**
     * @return SchemaProviderInterface
     */
    protected function getSchema(): SchemaProviderInterface
    {
        if ($this->schema) {
            return $this->schema;
        }

        throw new RuntimeException(sprintf(
            'Expecting schema to be set in adapters %s.',
            get_class($this),
        ));
    }

    /**
     * @return string
     */
    private function guessRelation()
    {
        [$one, $two, $caller] = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 3);

        return $this->modelRelationForField($caller['function']);
    }

}
