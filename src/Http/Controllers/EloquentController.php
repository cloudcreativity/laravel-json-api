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

namespace CloudCreativity\LaravelJsonApi\Http\Controllers;

use Closure;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\JsonApi\Contracts\Hydrator\HydratesRelatedInterface;
use CloudCreativity\JsonApi\Contracts\Hydrator\HydratorInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Document\GeneratesLinks;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Class EloquentController
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class EloquentController extends Controller
{

    use HandlesResourceRequests,
        GeneratesLinks;

    /**
     * @var HydratorInterface
     */
    protected $hydrator;

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
     *
     * @param Model $model
     * @param HydratorInterface|null $hydrator
     */
    public function __construct(Model $model, HydratorInterface $hydrator = null)
    {
        $this->model = $model;
        $this->hydrator = $hydrator;
    }

    /**
     * @param RequestInterface $request
     * @return Response
     */
    public function index(RequestInterface $request)
    {
        $result = $this->search($request);

        if ($result instanceof Response) {
            return $result;
        }

        return $this->reply()->content($result);
    }

    /**
     * @param RequestInterface $request
     * @return Response
     */
    public function create(RequestInterface $request)
    {
        $resource = $request->getDocument()->getResource();
        $model = $this->hydrate($resource, $this->model);
        $result = ($model instanceof Response) ? $model : $this->doCommit($model, $resource);

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
     * @param RequestInterface $request
     * @return Response
     */
    public function read(RequestInterface $request)
    {
        return $this->reply()->content(
            $this->getRecord($request)
        );
    }

    /**
     * @param RequestInterface $request
     * @return Response
     */
    public function update(RequestInterface $request)
    {
        $resource = $request->getDocument()->getResource();
        $model = $this->hydrate($resource, $this->getRecord($request));
        $result = ($model instanceof Response) ? $model : $this->doCommit($model, $resource);

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
     * @param RequestInterface $request
     * @return Response
     */
    public function delete(RequestInterface $request)
    {
        $model = $this->getRecord($request);
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
     * @param RequestInterface $request
     * @return Response
     */
    public function readRelatedResource(RequestInterface $request)
    {
        $model = $this->getRecord($request);
        $key = $this->keyForRelationship($request->getRelationshipName());

        return $this
            ->reply()
            ->content($model->{$key});
    }

    /**
     * @param RequestInterface $request
     * @return Response
     */
    public function readRelationship(RequestInterface $request)
    {
        $model = $this->getRecord($request);
        $key = $this->keyForRelationship($request->getRelationshipName());

        return $this
            ->reply()
            ->relationship($model->{$key});
    }

    /**
     * @param RequestInterface $request
     * @return mixed
     */
    protected function search(RequestInterface $request)
    {
        return $this->store()->query(
            $request->getResourceType(),
            $request->getParameters()
        );
    }

    /**
     * Hydrate the model with the supplied resource data.
     *
     * Child classes can overload this method if they need to do any logic pre- or
     * post- hydration.
     *
     * @param ResourceObjectInterface $resource
     * @param Model $model
     * @return Response|Model
     */
    protected function hydrate(ResourceObjectInterface $resource, Model $model)
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
     * @param ResourceObjectInterface $resource
     * @return bool|Response
     */
    protected function commit(Model $model, ResourceObjectInterface $resource)
    {
        $isUpdating = $model->exists;

        $this->beforeCommit($model, $resource, $isUpdating);

        $result = $this->save($model, $resource);

        if ($result) {
            $this->afterCommit($model, $resource, $isUpdating);
        }

        return $result;
    }

    /**
     * Execute a save.
     *
     * Child classes can overload this method if they need to implement additional writing to the database
     * on save. For example, if the resource includes a has-many relationship, that will have to be persisted
     * to the database after the primary model is saved if creating that model.
     *
     * @param Model $model
     * @param ResourceObjectInterface $resource
     * @return bool
     */
    protected function save(Model $model, ResourceObjectInterface $resource)
    {
        /** We save the primary model */
        if (!$model->save()) {
            return false;
        }

        /** If needed, we trigger hydration of secondary resources/has-many relationships and persist the
         * changes on any returned models. */
        if ($this->hydrator instanceof HydratesRelatedInterface) {
            $related = (array) $this->hydrator->hydrateRelated($resource, $model);

            foreach ($related as $relatedResource) {
                if (!$this->saveRelated($relatedResource)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Save a related resource that has been hydrated.
     *
     * @param object $related
     * @return bool
     */
    protected function saveRelated($related)
    {
        if ($related instanceof Model) {
            return $related->save();
        }

        return false;
    }

    /**
     * Determines which callback to use before creating or updating a model.
     *
     * @param Model $model
     * @param ResourceObjectInterface $resource
     * @param bool $isUpdating
     */
    protected function beforeCommit(Model $model, ResourceObjectInterface $resource, $isUpdating)
    {
        /** Trigger the saving hook if it is implemented */
        if (method_exists($this, 'saving')) {
            $this->saving($model, $resource);
        }

        $fn = $isUpdating ? 'updating' : 'creating';

        /** Trigger the updating or creating hook if it is implemented */
        if (method_exists($this, $fn)) {
            call_user_func([$this, $fn], $model, $resource);
        }
    }

    /**
     * Determines which callback to use after a model is updated or created.
     *
     * @param Model $model
     * @param ResourceObjectInterface $resource
     * @param bool $isUpdating
     */
    protected function afterCommit(Model $model, ResourceObjectInterface $resource, $isUpdating)
    {
        $fn = $isUpdating ? 'updated' : 'created';

        /** Trigger the updated or created hook if it is implemented */
        if (method_exists($this, $fn)) {
            call_user_func([$this, $fn], $model, $resource);
        }

        /** Trigger the saved hook if it is implemented */
        if (method_exists($this, 'saved')) {
            $this->saved($model, $resource);
        }
    }

    /**
     * Remove the model from the database.
     *
     * @param Model $model
     * @return bool|Response
     */
    protected function destroy(Model $model)
    {
        /** Trigger the deleting hook if it is implemented */
        if (method_exists($this, 'deleting')) {
            $this->deleting($model);
        }

        $result = (bool) $model->delete();

        /** Trigger the deleted hook if it is implemented and delete was successful */
        if ($result && method_exists($this, 'deleted')) {
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
            $this->relationships[$relationshipName] : Str::camel($relationshipName);
    }

    /**
     * @param RequestInterface $request
     * @return Model
     */
    protected function getRecord(RequestInterface $request)
    {
        $record = $request->getRecord();

        if (!$record instanceof Model) {
            throw new RuntimeException(sprintf('%s expects to be used with a %s record.', static::class, Model::class));
        }

        return $record;
    }

    /**
     * @return Response
     */
    protected function notImplemented()
    {
        return $this
            ->reply()
            ->statusCode(Response::HTTP_NOT_IMPLEMENTED);
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
     * @param ResourceObjectInterface $resource
     * @return bool|Response
     */
    private function doCommit(Model $model, ResourceObjectInterface $resource)
    {
        return $this->transaction(function () use ($model, $resource) {
            return $this->commit($model, $resource);
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

}
