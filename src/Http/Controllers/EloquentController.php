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

use CloudCreativity\JsonApi\Contracts\Hydrator\HydratorInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceInterface;
use CloudCreativity\LaravelJsonApi\Http\Requests\AbstractRequest;
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
     * @var Model
     */
    private $model;

    /**
     * @var HydratorInterface
     */
    private $hydrator;

    /**
     * EloquentController constructor.
     * @param Model $model
     * @param AbstractRequest $request
     * @param HydratorInterface $hydrator
     */
    public function __construct(Model $model, AbstractRequest $request, HydratorInterface $hydrator)
    {
        parent::__construct($request);
        $this->model = $model;
        $this->hydrator = $hydrator;
    }

    /**
     * @return Response
     */
    public function index()
    {
        $models = $this->model->all();

        return $this
            ->reply()
            ->content($models);
    }

    /**
     * @return Response
     */
    public function create()
    {
        $model = $this->hydrate($this->resource(), $this->model);

        if (!$this->commit($model)) {
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
            ->content($this->record());
    }

    /**
     * @param $resourceId
     * @return Response
     */
    public function update($resourceId)
    {
        $model = $this->hydrate($this->resource(), $this->record());

        if (!$this->commit($model)) {
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
        $model = $this->record();

        if (!$model->delete()) {
            return $this->internalServerError();
        }

        return $this
            ->reply()
            ->noContent();
    }

    /**
     * Hydrate the model with the supplied resource data.
     *
     * Child classes can overload this method if they need to do any logic pre- or
     * post- hydration.
     *
     * @param ResourceInterface $resource
     * @param Model $model
     * @return Model
     */
    protected function hydrate(ResourceInterface $resource, Model $model)
    {
        $this->hydrator->hydrate($resource, $model);

        return $model;
    }

    /**
     * Commit the model to the database.
     *
     * Child classes can overload this method if they need to do any logic pre- or
     * post-save.
     *
     * @param Model $model
     * @return bool
     */
    protected function commit(Model $model)
    {
        return $model->save();
    }

    /**
     * Remove the model from the database.
     *
     * Child classes can overload this method if they need to do any logic pre- or
     * post-delete.
     *
     * @param Model $model
     * @return bool
     */
    protected function destroy(Model $model)
    {
        return (bool) $model->delete();
    }

    /**
     * @return Model
     */
    protected function record()
    {
        $record = parent::record();

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
}
