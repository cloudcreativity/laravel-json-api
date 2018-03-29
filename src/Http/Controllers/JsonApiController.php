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
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
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
     * Whether database transactions should be used.
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

        if ($record instanceof Response) {
            return $record;
        }

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

        if ($record instanceof Response) {
            return $record;
        }

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
        $result = $this->transaction(function () use ($store, $request, $record) {
            return $this->doDelete($store, $record, $request->getParameters());
        });

        if ($result instanceof Response) {
            return $result;
        }

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
        $result = $this->transaction(function () use ($store, $request, $record) {
            return $this->doReplaceRelationship(
                $store,
                $record,
                $request->getRelationshipName(),
                $request->getDocument()->getRelationship(),
                $request->getParameters()
            );
        });

        if ($result instanceof Response) {
            return $result;
        }

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
        $result = $this->transaction(function () use ($store, $request, $record) {
            return $store->addToRelationship(
                $record,
                $request->getRelationshipName(),
                $request->getDocument()->getRelationship(),
                $request->getParameters()
            );
        });

        if ($result instanceof Response) {
            return $result;
        }

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
        $result = $this->transaction(function () use ($store, $request, $record) {
            return $store->removeFromRelationship(
                $record,
                $request->getRelationshipName(),
                $request->getDocument()->getRelationship(),
                $request->getParameters()
            );
        });

        if ($result instanceof Response) {
            return $result;
        }

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
     * @return object|Response
     *      the created record or a HTTP response.
     */
    protected function doCreate(
        StoreInterface $store,
        $resourceType,
        ResourceObjectInterface $resource,
        EncodingParametersInterface $parameters
    ) {
        $response = $this->beforeCommit($resource);

        if ($response instanceof Response) {
            return $response;
        }

        $record = $store->createRecord($resourceType, $resource, $parameters);
        $response = $this->afterCommit($resource, $record, false);

        return ($response instanceof Response) ? $response : $record;
    }

    /**
     * @param StoreInterface $store
     * @param $record
     * @param ResourceObjectInterface $resource
     * @param EncodingParametersInterface $parameters
     * @return object|Response
     *      the updated record or a HTTP response.
     */
    protected function doUpdate(
        StoreInterface $store,
        $record,
        ResourceObjectInterface $resource,
        EncodingParametersInterface $parameters
    ) {
        $response = $this->beforeCommit($resource, $record);

        if ($response instanceof Response) {
            return $response;
        }

        $record = $store->updateRecord($record, $resource, $parameters);
        $response = $this->afterCommit($resource, $record, true);

        return ($response instanceof Response) ? $response : $record;
    }

    /**
     * @param StoreInterface $store
     * @param $record
     * @param EncodingParametersInterface $parameters
     * @return Response|null
     *      an HTTP response or null.
     */
    protected function doDelete(StoreInterface $store, $record, EncodingParametersInterface $parameters)
    {
        $response = null;

        if (method_exists($this, 'deleting')) {
            $response = $this->deleting($record);
        }

        if ($response instanceof Response) {
            return $response;
        }

        $store->deleteRecord($record, $parameters);

        if (method_exists($this, 'deleted')) {
            $response = $this->deleted($record);
        }

        return $response;
    }

    /**
     * @param StoreInterface $store
     * @param $record
     * @param $relationshipKey
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $params
     * @return Response|null
     *      an HTTP response or null.
     */
    protected function doReplaceRelationship(
        StoreInterface $store,
        $record,
        $relationshipKey,
        RelationshipInterface $relationship,
        EncodingParametersInterface $params
    ) {
        $store->replaceRelationship(
            $record,
            $relationshipKey,
            $relationship,
            $params
        );

        return null;
    }

    /**
     * @param StoreInterface $store
     * @param $record
     * @param $relationshipKey
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $params
     * @return Response|null
     *      an HTTP response or null.
     */
    protected function doAddToRelationship(
        StoreInterface $store,
        $record,
        $relationshipKey,
        RelationshipInterface $relationship,
        EncodingParametersInterface $params
    ) {
        $store->addToRelationship(
            $record,
            $relationshipKey,
            $relationship,
            $params
        );

        return null;
    }

    /**
     * @param StoreInterface $store
     * @param $record
     * @param $relationshipKey
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $params
     * @return Response|null
     */
    protected function doRemoveFromRelationship(
        StoreInterface $store,
        $record,
        $relationshipKey,
        RelationshipInterface $relationship,
        EncodingParametersInterface $params
    ) {
        $store->removeFromRelationship(
            $record,
            $relationshipKey,
            $relationship,
            $params
        );

        return null;
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
     * @return Response|null
     */
    private function beforeCommit(ResourceObjectInterface $resource, $record = null)
    {
        $result = method_exists($this, 'saving') ? $this->saving($record, $resource) : null;

        if ($result instanceof Response) {
            return $result;
        }

        if (is_null($record) && method_exists($this, 'creating')) {
            $result = $this->creating($resource);
        } elseif ($record && method_exists($this, 'updating')) {
            $result = $this->updating($record, $resource);
        }

        return $result;
    }

    /**
     * @param ResourceObjectInterface $resource
     * @param $record
     * @param $updating
     * @return Response|null
     */
    private function afterCommit(ResourceObjectInterface $resource, $record, $updating)
    {
        $fn = !$updating ? 'created' : 'updated';
        $result = method_exists($this, $fn) ? $this->{$fn}($record, $resource) : null;

        if ($result instanceof Response) {
            return $result;
        }

        return method_exists($this, 'saved') ? $this->saved($record, $resource) : null;
    }

}
