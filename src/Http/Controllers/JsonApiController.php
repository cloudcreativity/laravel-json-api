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

namespace CloudCreativity\LaravelJsonApi\Http\Controllers;

use Closure;
use CloudCreativity\LaravelJsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest;
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
     * Index action.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response
     */
    public function index(StoreInterface $store, ValidatedRequest $request)
    {
        return $this->reply()->content(
            $this->doSearch($store, $request)
        );
    }

    /**
     * Read resource action.
     *
     * @param ValidatedRequest $request
     * @return Response
     */
    public function read(ValidatedRequest $request)
    {
        $record = $request->getRecord();

        if (method_exists($this, 'reading')) {
            $this->reading($record, $request);
        }

        return $this->reply()->content($record);
    }

    /**
     * Create resource action.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response
     */
    public function create(StoreInterface $store, ValidatedRequest $request)
    {
        $record = $this->transaction(function () use ($store, $request) {
            return $this->doCreate($store, $request);
        });

        if ($record instanceof Response) {
            return $record;
        }

        return $this->reply()->created($record);
    }

    /**
     * Update resource action.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response
     */
    public function update(StoreInterface $store, ValidatedRequest $request)
    {
        $record = $this->transaction(function () use ($store, $request) {
            return $this->doUpdate($store, $request);
        });

        if ($record instanceof Response) {
            return $record;
        }

        return $this->reply()->content($record);
    }

    /**
     * Delete resource action.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response
     */
    public function delete(StoreInterface $store, ValidatedRequest $request)
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $this->doDelete($store, $request);
        });

        if ($result instanceof Response) {
            return $result;
        }

        return $this->reply()->noContent();
    }

    /**
     * Read related resource action.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response
     */
    public function readRelatedResource(StoreInterface $store, ValidatedRequest $request)
    {
        $related = $store->queryRelated(
            $request->getRecord(),
            $request->getRelationshipName(),
            $request->getParameters()
        );

        return $this->reply()->content($related);
    }

    /**
     * Read relationship data action.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response
     */
    public function readRelationship(StoreInterface $store, ValidatedRequest $request)
    {
        $related = $store->queryRelationship(
            $request->getRecord(),
            $request->getRelationshipName(),
            $request->getParameters()
        );

        return $this->reply()->relationship($related);
    }

    /**
     * Replace relationship data action.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response
     */
    public function replaceRelationship(StoreInterface $store, ValidatedRequest $request)
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $this->doReplaceRelationship(
                $store,
                $request->getRecord(),
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
     * Add to relationship data action.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response
     */
    public function addToRelationship(StoreInterface $store, ValidatedRequest $request)
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $store->addToRelationship(
                $request->getRecord(),
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
     * Remove from relationship data action.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response
     */
    public function removeFromRelationship(StoreInterface $store, ValidatedRequest $request)
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $store->removeFromRelationship(
                $request->getRecord(),
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
     * Search resources.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return mixed
     */
    protected function doSearch(StoreInterface $store, ValidatedRequest $request)
    {
        if (method_exists($this, 'searching')) {
            $this->searching($request);
        }

        return $store->queryRecords($request->getResourceType(), $request->getParameters());
    }

    /**
     * Create a resource.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return object|Response
     *      the created record or a HTTP response.
     */
    protected function doCreate(StoreInterface $store, ValidatedRequest $request)
    {
        $response = $this->beforeCommit($request);

        if ($response instanceof Response) {
            return $response;
        }

        $record = $store->createRecord(
            $request->getResourceType(),
            $request->getDocument()->getResource(),
            $request->getParameters()
        );

        $response = $this->afterCommit($request, $record, false);

        return ($response instanceof Response) ? $response : $record;
    }

    /**
     * Update a resource.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return object|Response
     *      the updated record or a HTTP response.
     */
    protected function doUpdate(StoreInterface $store, ValidatedRequest $request)
    {
        $response = $this->beforeCommit($request);

        if ($response instanceof Response) {
            return $response;
        }

        $record = $store->updateRecord(
            $request->getRecord(),
            $request->getDocument()->getResource(),
            $request->getParameters()
        );

        $response = $this->afterCommit($request, $record, true);

        return ($response instanceof Response) ? $response : $record;
    }

    /**
     * Delete a resource.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response|null
     *      an HTTP response or null.
     */
    protected function doDelete(StoreInterface $store, ValidatedRequest $request)
    {
        $record = $request->getRecord();
        $response = null;

        if (method_exists($this, 'deleting')) {
            $response = $this->deleting($record, $request);
        }

        if ($response instanceof Response) {
            return $response;
        }

        $store->deleteRecord($record, $request->getParameters());

        if (method_exists($this, 'deleted')) {
            $response = $this->deleted($record, $request);
        }

        return $response;
    }

    /**
     * Replace a relationship.
     *
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
     * Add to a relationship.
     *
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
     * Remove from a relationship.
     *
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
     * Execute the closure within an optional transaction.
     *
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
     * @param ValidatedRequest $request
     * @return Response|null
     */
    private function beforeCommit(ValidatedRequest $request)
    {
        $record = $request->getRecord();
        $result = method_exists($this, 'saving') ? $this->saving($record, $request) : null;

        if ($result instanceof Response) {
            return $result;
        }

        if (is_null($record) && method_exists($this, 'creating')) {
            $result = $this->creating($request);
        } elseif ($record && method_exists($this, 'updating')) {
            $result = $this->updating($record, $request);
        }

        return $result;
    }

    /**
     * @param ValidatedRequest $request
     * @param $record
     * @param $updating
     * @return Response|null
     */
    private function afterCommit(ValidatedRequest $request, $record, $updating)
    {
        $fn = !$updating ? 'created' : 'updated';
        $result = method_exists($this, $fn) ? $this->{$fn}($record, $request) : null;

        if ($result instanceof Response) {
            return $result;
        }

        return method_exists($this, 'saved') ? $this->saved($record, $request) : null;
    }

}
