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
use CloudCreativity\JsonApi\Contracts\Http\Requests\InboundRequestInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class JsonApiController
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiController extends Controller
{

    use CreatesResponses;

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
     * @param StoreInterface $store
     * @param InboundRequestInterface $request
     * @return Response
     */
    public function index(StoreInterface $store, InboundRequestInterface $request)
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
     * @param InboundRequestInterface $request
     * @return Response
     */
    public function create(StoreInterface $store, InboundRequestInterface $request)
    {
        $record = $this->transaction(function () use ($store, $request) {
            return $this->doCreate(
                $store,
                $request->getResourceType(),
                $request->getDocument()->getResource(),
                $request->getParameters()
            );
        });

        return $this->reply()->created($record);
    }

    /**
     * @param StoreInterface $store
     * @param InboundRequestInterface $request
     * @param object $record
     * @return Response
     */
    public function update(StoreInterface $store, InboundRequestInterface $request, $record)
    {
        $record = $this->transaction(function () use ($store, $request, $record) {
            return $this->doUpdate(
                $store,
                $record,
                $request->getDocument()->getResource(),
                $request->getParameters()
            );
        });

        return $this->reply()->content($record);
    }

    /**
     * @param StoreInterface $store
     * @param InboundRequestInterface $request
     * @param $record
     * @return Response
     */
    public function delete(StoreInterface $store, InboundRequestInterface $request, $record)
    {
        $this->transaction(function () use ($store, $request, $record) {
            $this->doDelete($store, $record, $request->getParameters());
        });

        return $this->reply()->noContent();
    }

    /**
     * @param StoreInterface $store
     * @param InboundRequestInterface $request
     * @param object $record
     * @return Response
     */
    public function readRelatedResource(StoreInterface $store, InboundRequestInterface $request, $record)
    {
        $related = $store->queryRelated(
            $record,
            $request->getRelationshipName(),
            $request->getParameters()
        );

        return $this->reply()->content($related);
    }

    /**
     * @param StoreInterface $store
     * @param InboundRequestInterface $request
     * @param $record
     * @return Response
     */
    public function readRelationship(StoreInterface $store, InboundRequestInterface $request, $record)
    {
        $related = $store->queryRelationship(
            $record,
            $request->getRelationshipName(),
            $request->getParameters()
        );

        return $this->reply()->relationship($related);
    }

    /**
     * @param StoreInterface $store
     * @param InboundRequestInterface $request
     * @param $record
     * @return Response
     */
    public function replaceRelationship(StoreInterface $store, InboundRequestInterface $request, $record)
    {
        $this->transaction(function () use ($store, $request, $record) {
            $store->replaceRelationship(
                $record,
                $request->getRelationshipName(),
                $request->getDocument()->getRelationship(),
                $request->getParameters()
            );
        });

        return $this->reply()->noContent();
    }

    /**
     * @param StoreInterface $store
     * @param InboundRequestInterface $request
     * @param $record
     * @return Response
     */
    public function addToRelationship(StoreInterface $store, InboundRequestInterface $request, $record)
    {
        $this->transaction(function () use ($store, $request, $record) {
            $store->addToRelationship(
                $record,
                $request->getRelationshipName(),
                $request->getDocument()->getRelationship(),
                $request->getParameters()
            );
        });

        return $this->reply()->noContent();
    }

    /**
     * @param StoreInterface $store
     * @param InboundRequestInterface $request
     * @param $record
     * @return Response
     */
    public function removeFromRelationship(StoreInterface $store, InboundRequestInterface $request, $record)
    {
        $this->transaction(function () use ($store, $request, $record) {
            $store->removeFromRelationship(
                $record,
                $request->getRelationshipName(),
                $request->getDocument()->getRelationship(),
                $request->getParameters()
            );
        });

        return $this->reply()->noContent();
    }

    /**
     * @param StoreInterface $store
     * @param InboundRequestInterface $request
     * @return mixed
     */
    protected function doSearch(StoreInterface $store, InboundRequestInterface $request)
    {
        return $store->queryRecords($request->getResourceType(), $request->getParameters());
    }

    /**
     * @param StoreInterface $store
     * @param string $resourceType
     * @param ResourceObjectInterface $resource
     * @param EncodingParametersInterface $parameters
     * @return object
     */
    protected function doCreate(
        StoreInterface $store,
        $resourceType,
        ResourceObjectInterface $resource,
        EncodingParametersInterface $parameters
    ) {
        $this->beforeCommit($resource);
        $record = $store->createRecord($resourceType, $resource, $parameters);
        $this->afterCommit($resource, $record, false);

        return $record;
    }

    /**
     * @param StoreInterface $store
     * @param $record
     * @param ResourceObjectInterface $resource
     * @param EncodingParametersInterface $parameters
     * @return object
     */
    protected function doUpdate(
        StoreInterface $store,
        $record,
        ResourceObjectInterface $resource,
        EncodingParametersInterface $parameters
    ) {
        $this->beforeCommit($resource, $record);
        $record = $store->updateRecord($record, $resource, $parameters);
        $this->afterCommit($resource, $record, true);

        return $record;
    }

    /**
     * @param StoreInterface $store
     * @param $record
     * @param EncodingParametersInterface $parameters
     */
    protected function doDelete(StoreInterface $store, $record, EncodingParametersInterface $parameters)
    {
        if (method_exists($this, 'deleting')) {
            $this->deleting($record);
        }

        $store->deleteRecord($record, $parameters);

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
