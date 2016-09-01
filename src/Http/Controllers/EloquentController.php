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

namespace CloudCreativity\LaravelJsonApi\Http\Controllers;

use Closure;
use CloudCreativity\JsonApi\Contracts\Hydrator\HydratorInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestHandlerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Search\SearchInterface;
use CloudCreativity\LaravelJsonApi\Search\SearchAll;
use Exception;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use RuntimeException;

/**
 * Class EloquentController
 * @package CloudCreativity\LaravelJsonApi
 */
class EloquentController extends JsonApiController
{

    /**
     * @var HydratorInterface
     */
    protected $hydrator;

    /**
     * @var SearchInterface
     */
    protected $search;

    /**
     * Map of URI relationship names to model relationship keys.
     *
     * By default the URI relationship name will be camel-cased to get the model
     * relationship name. You can override this default for a particular relationship
     * by entering a mapping in this array as a key/value pair - URI relationship
     * name as the key, model relationship name as the value.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * @var Model
     */
    private $model;

    /**
     * EloquentController constructor.
     * @param Model $model
     * @param RequestHandlerInterface $request
     * @param HydratorInterface|null $hydrator
     * @param SearchInterface|null $search
     */
    public function __construct(
        Model $model,
        RequestHandlerInterface $request,
        HydratorInterface $hydrator = null,
        SearchInterface $search = null
    ) {
        parent::__construct($request);
        $this->model = $model;
        $this->hydrator = $hydrator;
        $this->search = $search ?: new SearchAll();
    }

    /**
     * @return Response
     */
    public function index()
    {
        $result = $this->search();

        return $this
            ->reply()
            ->content($result);
    }

    /**
     * @return Response
     */
    public function create()
    {
        $model = $this->hydrate($this->getResource(), $this->model);
        $result = ($model instanceof Response) ? $model : $this->doCommit($model);

        if ($result instanceof Response) {
            return $result;
        } elseif (!$result) {
            return $this->internalServerError();
        }

        return $this
            ->reply()
            ->created($this->model);
    }

    /**
     * @param $resourceId
     * @return Response
     */
    public function read($resourceId)
    {
        return $this
            ->reply()
            ->content($this->getRecord());
    }

    /**
     * @param $resourceId
     * @return Response
     */
    public function update($resourceId)
    {
        $model = $this->hydrate($this->getResource(), $this->getRecord());
        $result = ($model instanceof Response) ? $model : $this->doCommit($model);

        if ($result instanceof Response) {
            return $result;
        } elseif (!$result) {
            return $this->internalServerError();
        }

        return $this
            ->reply()
            ->content($model);
    }

    /**
     * @param $resourceId
     * @return Response
     */
    public function delete($resourceId)
    {
        $model = $this->getRecord();
        $result = $this->doDestroy($model);

        if ($result instanceof Response) {
            return $result;
        } elseif (!$result) {
            return $this->internalServerError();
        }

        return $this
            ->reply()
            ->noContent();
    }

    /**
     * @param $resourceId
     * @param $relationshipName
     * @return Response
     */
    public function readRelatedResource($resourceId, $relationshipName)
    {
        $model = $this->getRecord();
        $key = $this->keyForRelationship($relationshipName);

        return $this
            ->reply()
            ->content($model->{$key});
    }

    /**
     * @param $resourceId
     * @param $relationshipName
     * @return Response
     */
    public function readRelationship($resourceId, $relationshipName)
    {
        $model = $this->getRecord();
        $key = $this->keyForRelationship($relationshipName);

        return $this
            ->reply()
            ->relationship($model->{$key});
    }

    /**
     * @return Paginator|Collection|Model|null
     */
    protected function search()
    {
        if (!$this->search) {
            return $this->model->all();
        }

        $builder = $this->model->newQuery();
        $parameters = $this->getRequestHandler()->getEncodingParameters();

        return $this->search->search($builder, $parameters);
    }

    /**
     * Hydrate the model with the supplied resource data.
     *
     * Child classes can overload this method if they need to do any logic pre- or
     * post- hydration.
     *
     * @param ResourceInterface $resource
     * @param Model $model
     * @return Response|Model
     */
    protected function hydrate(ResourceInterface $resource, Model $model)
    {
        /** If there is no hydrator, the method cannot be allowed. */
        if (!$this->hydrator) {
            return $this->methodNotAllowed();
        }

        return $this->hydrator->hydrate($resource, $model);
    }

    /**
     * Commit the model to the database.
     *
     * @param Model $model
     * @return bool|Response
     */
    protected function commit(Model $model)
    {
        $isUpdating = $model->exists;

        $this->beforeCommit($model, $isUpdating);

        $result = $model->save();

        if ($result) {
            $this->afterCommit($model, $isUpdating);
        }

        return $result;
    }

    /**
     * Determines which callback to use before creating or updating a model.
     *
     * @param Model $model
     * @param bool $isUpdating
     */
    protected function beforeCommit(Model $model, $isUpdating)
    {
        $this->saving($model);

        if ($isUpdating) {
            $this->updating($model);
        } else {
            $this->creating($model);
        }
    }

    /**
     * Determines which callback to use after a model is updated or created.
     *
     * @param Model $model
     * @param bool $isUpdating
     */
    protected function afterCommit(Model $model, $isUpdating)
    {
        if ($isUpdating) {
            $this->updated($model);
        } else {
            $this->created($model);
        }

        $this->saved($model);
    }

    /**
     * Remove the model from the database.
     *
     * @param Model $model
     * @return bool|Response
     */
    protected function destroy(Model $model)
    {
        $this->deleting($model);

        $result = (bool) $model->delete();

        if ($result) {
            $this->deleted($model);
        }

        return $result;
    }

    /**
     * @param Closure $closure
     * @return mixed
     * @throws Exception
     */
    protected function transaction(Closure $closure)
    {
        $connection = $this->model->getConnection();
        $connection->beginTransaction();

        try {
            $result = $closure();
            $connection->commit();
            return $result;
        } catch (Exception $e) {
            $connection->rollBack();
            throw $e;
        }
    }

    /**
     * Convert a relationship name into the attribute name to get the relationship from the model.
     *
     * @param $relationshipName
     *      the relationship name as it appears in the uri.
     * @return string
     *      the key to use on the model.
     */
    protected function keyForRelationship($relationshipName)
    {
        return isset($this->relationships[$relationshipName]) ?
            $this->relationships[$relationshipName] : camel_case($relationshipName);
    }

    /**
     * @return Model
     */
    protected function getRecord()
    {
        $record = parent::getRecord();

        if (!$record instanceof Model) {
            throw new RuntimeException(sprintf('%s expects to be used with a %s record.', static::class, Model::class));
        }

        return $record;
    }

    /**
     * @return Response
     */
    protected function internalServerError()
    {
        return $this->reply()->statusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @return Response
     */
    protected function methodNotAllowed()
    {
        return $this->reply()->statusCode(Response::HTTP_METHOD_NOT_ALLOWED);
    }

    /**
     * Perform the commit task within a transaction.
     *
     * @param Model $model
     * @return bool|Response
     */
    private function doCommit(Model $model)
    {
        return $this->transaction(function () use ($model) {
            return $this->commit($model);
        });
    }

    /**
     * Perform a destroy task within a transaction.
     *
     * @param Model $model
     * @return bool|Response
     */
    private function doDestroy(Model $model)
    {
        return $this->transaction(function () use ($model) {
            return $this->destroy($model);
        });
    }

    /**
     * Called before the model is saved (either creating or updating an existing model).
     *
     * Child classes can overload this method if they need to do any logic pre-save.
     *
     * @param Model $model
     */
    protected function saving(Model $model)
    {
    }

    /**
     * Called after the model has been saved (when a model has been created or updated)
     *
     * Child classes can overload this method if they need to do any logic post-save.
     *
     * @param Model $model
     */
    protected function saved(Model $model)
    {
    }

    /**
     * Called before the model is created.
     *
     * Child classes can overload this method if they need to do any logic pre-creation.
     *
     * @param Model $model
     */
    protected function creating(Model $model)
    {
    }

    /**
     * Called after the model has been created.
     *
     * Child classes can overload this method if they need to do any logic post-creation.
     *
     * @param Model $model
     */
    protected function created(Model $model)
    {
    }

    /**
     * Called before the model is updated.
     *
     * Child classes can overload this method if they need to do any logic pre-updating.
     *
     * @param Model $model
     */
    protected function updating(Model $model)
    {
    }

    /**
     * Called after the model has been updated.
     *
     * Child classes can overload this method if they need to do any logic post-updating.
     *
     * @param Model $model
     */
    protected function updated(Model $model)
    {
    }

    /**
     * Called before the model is destroyed.
     *
     * Child classes can overload this method if they need to do any logic pre-delete.
     *
     * @param Model $model
     */
    protected function deleting(Model $model)
    {
    }

    /**
     * Called after the model has been destroyed.
     *
     * Child classes can overload this method if they need to do any logic post-delete.
     *
     * @param Model $model
     */
    protected function deleted(Model $model)
    {
    }
}
