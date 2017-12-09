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
use CloudCreativity\JsonApi\Contracts\Hydrator\HydratorInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Class JsonApiController
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class JsonApiController extends Controller
{

    use CreatesResponses;

    /**
     * The hydrator fully-qualified class name, or service name.
     *
     * @var HydratorInterface|string|null
     */
    protected $hydrator;

    /**
     * The database connection name to use for transactions, or null for the default connection.
     *
     * @var string|null
     */
    protected $connection;

    /**
     * Whether database transaction should be used.
     *
     * @var bool
     */
    protected $useTransactions = true;

    /**
     * @param $record
     * @return bool
     *      whether the record was successfully deleted.
     * @todo move to store
     */
    abstract protected function destroyRecord($record);

    /**
     * @param StoreInterface $store
     * @param RequestInterface $request
     * @return Response
     */
    public function index(StoreInterface $store, RequestInterface $request)
    {
        return $this->reply()->content(
            $this->doSearch($store, $request)
        );
    }

    /**
     * @param object $record
     * @return Response
     */
    public function read($record)
    {
        return $this->reply()->content($record);
    }

    /**
     * @param StoreInterface $store
     * @param ResourceObjectInterface $resource
     * @return Response
     */
    public function create(StoreInterface $store, ResourceObjectInterface $resource)
    {
        $record = $this->transaction(function () use ($store, $resource) {
            return $this->doCreate($store, $resource);
        });

        return $this->reply()->created($record);
    }

    /**
     * @param StoreInterface $store
     * @param ResourceObjectInterface $resource
     * @param object $record
     * @return Response
     */
    public function update(StoreInterface $store, ResourceObjectInterface $resource, $record)
    {
        $record = $this->transaction(function () use ($store, $resource, $record) {
            return $this->doUpdate($store, $resource, $record);
        });

        return $this->reply()->content($record);
    }

    /**
     * @param $record
     * @return Response
     */
    public function delete($record)
    {
        $this->transaction(function () use ($record) {
            $this->doDelete($record);
        });

        return $this->reply()->noContent();
    }

    /**
     * @param StoreInterface $store
     * @param RequestInterface $request
     * @param object $record
     * @return Response
     */
    public function readRelatedResource(StoreInterface $store, RequestInterface $request, $record)
    {
        $related = $store->queryRelated(
            $request->getResourceType(),
            $record,
            $request->getRelationshipName(),
            $request->getParameters()
        );

        return $this->reply()->content($related);
    }

    /**
     * @param StoreInterface $store
     * @param RequestInterface $request
     * @param $record
     * @return Response
     */
    public function readRelationship(StoreInterface $store, RequestInterface $request, $record)
    {
        $related = $store->queryRelationship(
            $request->getResourceType(),
            $record,
            $request->getRelationshipName(),
            $request->getParameters()
        );

        return $this->reply()->relationship($related);
    }

    /**
     * @param StoreInterface $store
     * @param RequestInterface $request
     * @param $record
     * @return Response
     */
    public function replaceRelationship(StoreInterface $store, RequestInterface $request, $record)
    {
        $this->transaction(function () use ($store, $request, $record) {
            $this->hydrator()->withStore($store)->updateRelationship(
                $request->getRelationshipName(),
                $request->getDocument()->getRelationship(),
                $record
            );
        });

        return $this->reply()->noContent();
    }

    /**
     * @param StoreInterface $store
     * @param RequestInterface $request
     * @param $record
     * @return Response
     */
    public function addToRelationship(StoreInterface $store, RequestInterface $request, $record)
    {
        $this->transaction(function () use ($store, $request, $record) {
            $this->hydrator()->withStore($store)->addToRelationship(
                $request->getRelationshipName(),
                $request->getDocument()->getRelationship(),
                $record
            );
        });

        return $this->reply()->noContent();
    }

    /**
     * @param StoreInterface $store
     * @param RequestInterface $request
     * @param $record
     * @return Response
     */
    public function removeFromRelationship(StoreInterface $store, RequestInterface $request, $record)
    {
        $this->transaction(function () use ($store, $request, $record) {
            $this->hydrator()->withStore($store)->removeFromRelationship(
                $request->getRelationshipName(),
                $request->getDocument()->getRelationship(),
                $record
            );
        });

        return $this->reply()->noContent();
    }

    /**
     * @param StoreInterface $store
     * @param RequestInterface $request
     * @return mixed
     */
    protected function doSearch(StoreInterface $store, RequestInterface $request)
    {
        return $store->query($request->getResourceType(), $request->getParameters());
    }

    /**
     * @param StoreInterface $store
     * @param ResourceObjectInterface $resource
     * @return object
     */
    protected function doCreate(StoreInterface $store, ResourceObjectInterface $resource)
    {
        $this->beforeCommit($resource);
        $record = $this->hydrator()->withStore($store)->create($resource);
        $this->afterCommit($resource, $record, false);

        return $record;
    }

    /**
     * @param StoreInterface $store
     * @param ResourceObjectInterface $resource
     * @param $record
     * @return object
     */
    protected function doUpdate(StoreInterface $store, ResourceObjectInterface $resource, $record)
    {
        $this->beforeCommit($resource, $record);
        $record = $this->hydrator()->withStore($store)->update($resource, $record);
        $this->afterCommit($resource, $record, true);

        return $record;
    }

    /**
     * @param $record
     * @return void
     */
    protected function doDelete($record)
    {
        if (method_exists($this, 'deleting')) {
            $this->deleting($record);
        }

        if (!$this->destroyRecord($record)) {
            throw new RuntimeException('Record was not successfully deleted.');
        }

        if (method_exists($this, 'deleted')) {
            $this->deleted($record);
        }
    }

    /**
     * @param Closure $closure
     * @return mixed
     */
    protected function transaction(Closure $closure)
    {
        if (!$this->useTransactions) {
            return $closure();
        }

        return app('db')->connection($this->connection)->transaction($closure);
    }

    /**
     * @return HydratorInterface
     */
    protected function hydrator()
    {
        if ($this->hydrator instanceof HydratorInterface) {
            return $this->hydrator;
        }

        if (!$this->hydrator) {
            throw new RuntimeException('The hydrator property must be set.');
        }

        $hydrator = app($this->hydrator);

        if (!$hydrator instanceof HydratorInterface) {
            throw new RuntimeException("Service $this->hydrator is not a hydrator.");
        }

        return $hydrator;
    }

    /**
     * @param ResourceObjectInterface $resource
     * @param object|null $record
     */
    protected function beforeCommit(ResourceObjectInterface $resource, $record = null)
    {
        if (method_exists($this, 'saving')) {
            $this->saving($resource, $record);
        }

        $fn = is_null($record) ? 'creating' : 'updating';

        if (method_exists($this, $fn)) {
            $this->{$fn}($resource, $record);
        }
    }

    /**
     * @param ResourceObjectInterface $resource
     * @param $record
     * @param $updating
     */
    protected function afterCommit(ResourceObjectInterface $resource, $record, $updating)
    {
        $fn = !$updating ? 'created' : 'updating';

        if (method_exists($this, $fn)) {
            $this->{$fn}($resource, $record);
        }

        if (method_exists($this, 'saved')) {
            $this->saved($resource, $record);
        }
    }

}
