<?php

/**
 * Copyright 2019 Cloud Creativity Limited
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
use CloudCreativity\LaravelJsonApi\Auth\AuthorizesRequests;
use CloudCreativity\LaravelJsonApi\Codec\ChecksMediaTypes;
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PageInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Http\Requests\CreateResource;
use CloudCreativity\LaravelJsonApi\Http\Requests\DeleteResource;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchProcess;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchProcesses;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchRelated;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchRelationship;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchResource;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchResources;
use CloudCreativity\LaravelJsonApi\Http\Requests\UpdateRelationship;
use CloudCreativity\LaravelJsonApi\Http\Requests\UpdateResource;
use CloudCreativity\LaravelJsonApi\Utils\InvokesHooks;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JsonApiController
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiController extends Controller
{

    use CreatesResponses,
        AuthorizesRequests,
        ChecksMediaTypes,
        InvokesHooks;

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
     * @param FetchResources $request
     * @return Response
     */
    public function index(StoreInterface $store, FetchResources $request)
    {
        $result = $this->doSearch($store, $request);

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->content($result);
    }

    /**
     * Read resource action.
     *
     * @param StoreInterface $store
     * @param FetchResource $request
     * @return Response
     */
    public function read(StoreInterface $store, FetchResource $request)
    {
        $result = $this->doRead($store, $request);

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->content($result);
    }

    /**
     * Create resource action.
     *
     * @param StoreInterface $store
     * @param CreateResource $request
     * @return Response
     */
    public function create(StoreInterface $store, CreateResource $request)
    {
        $record = $this->transaction(function () use ($store, $request) {
            return $this->doCreate($store, $request);
        });

        if ($this->isResponse($record)) {
            return $record;
        }

        return $this->reply()->created($record);
    }

    /**
     * Update resource action.
     *
     * @param StoreInterface $store
     * @param UpdateResource $request
     * @return Response
     */
    public function update(StoreInterface $store, UpdateResource $request)
    {
        $record = $this->transaction(function () use ($store, $request) {
            return $this->doUpdate($store, $request);
        });

        if ($this->isResponse($record)) {
            return $record;
        }

        return $this->reply()->updated($record);
    }

    /**
     * Delete resource action.
     *
     * @param StoreInterface $store
     * @param DeleteResource $request
     * @return Response
     */
    public function delete(StoreInterface $store, DeleteResource $request)
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $this->doDelete($store, $request);
        });

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->deleted($result);
    }

    /**
     * Read related resource action.
     *
     * @param StoreInterface $store
     * @param FetchRelated $request
     * @return Response
     */
    public function readRelatedResource(StoreInterface $store, FetchRelated $request)
    {
        $record = $request->getRecord();
        $result = $this->beforeReadingRelationship($record, $request);

        if ($this->isResponse($result)) {
            return $result;
        }

        $related = $store->queryRelated(
            $record,
            $request->getRelationshipName(),
            $request->getParameters()
        );

        $records = ($related instanceof PageInterface) ? $related->getData() : $related;
        $result = $this->afterReadingRelationship($record, $records, $request);

        if ($this->isInvokedResult($result)) {
            return $result;
        }

        return $this->reply()->content($related);
    }

    /**
     * Read relationship data action.
     *
     * @param StoreInterface $store
     * @param FetchRelationship $request
     * @return Response
     */
    public function readRelationship(StoreInterface $store, FetchRelationship $request)
    {
        $record = $request->getRecord();
        $result = $this->beforeReadingRelationship($record, $request);

        if ($this->isResponse($result)) {
            return $result;
        }

        $related = $store->queryRelationship(
            $record,
            $request->getRelationshipName(),
            $request->getParameters()
        );

        $records = ($related instanceof PageInterface) ? $related->getData() : $related;
        $result = $this->afterReadingRelationship($record, $records, $request);

        if ($this->isInvokedResult($result)) {
            return $result;
        }

        return $this->reply()->relationship($related);
    }

    /**
     * Replace relationship data action.
     *
     * @param StoreInterface $store
     * @param UpdateRelationship $request
     * @return Response
     */
    public function replaceRelationship(StoreInterface $store, UpdateRelationship $request)
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $this->doReplaceRelationship($store, $request);
        });

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->noContent();
    }

    /**
     * Add to relationship data action.
     *
     * @param StoreInterface $store
     * @param UpdateRelationship $request
     * @return Response
     */
    public function addToRelationship(StoreInterface $store, UpdateRelationship $request)
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $this->doAddToRelationship($store, $request);
        });

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->noContent();
    }

    /**
     * Remove from relationship data action.
     *
     * @param StoreInterface $store
     * @param UpdateRelationship $request
     * @return Response
     */
    public function removeFromRelationship(StoreInterface $store, UpdateRelationship $request)
    {
        $result = $this->transaction(function () use ($store, $request) {
            return $this->doRemoveFromRelationship($store, $request);
        });

        if ($this->isResponse($result)) {
            return $result;
        }

        return $this->reply()->noContent();
    }

    /**
     * Read processes action.
     *
     * @param StoreInterface $store
     * @param FetchProcesses $request
     * @return Response
     */
    public function processes(StoreInterface $store, FetchProcesses $request)
    {
        $result = $store->queryRecords(
            $request->getProcessType(),
            $request->getEncodingParameters()
        );

        return $this->reply()->content($result);
    }

    /**
     * Read a process action.
     *
     * @param StoreInterface $store
     * @param FetchProcess $request
     * @return Response
     */
    public function process(StoreInterface $store, FetchProcess $request)
    {
        $record = $store->readRecord(
            $request->getProcess(),
            $request->getEncodingParameters()
        );

        return $this->reply()->process($record);
    }

    /**
     * Search resources.
     *
     * @param StoreInterface $store
     * @param FetchResources $request
     * @return mixed
     */
    protected function doSearch(StoreInterface $store, FetchResources $request)
    {
        if ($result = $this->invoke('searching', $request)) {
            return $result;
        }

        $found = $store->queryRecords($request->getResourceType(), $request->getParameters());
        $records = ($found instanceof PageInterface) ? $found->getData() : $found;

        if ($result = $this->invoke('searched', $records, $request)) {
            return $result;
        }

        return $found;
    }

    /**
     * Read a resource.
     *
     * @param StoreInterface $store
     * @param FetchResource $request
     * @return mixed
     */
    protected function doRead(StoreInterface $store, FetchResource $request)
    {
        $record = $request->getRecord();

        if ($result = $this->invoke('reading', $record, $request)) {
            return $result;
        }

        /** We pass to the store for filtering, eager loading etc. */
        $record = $store->readRecord($record, $request->getParameters());

        if ($result = $this->invoke('didRead', $record, $request)) {
            return $result;
        }

        return $record;
    }

    /**
     * Create a resource.
     *
     * @param StoreInterface $store
     * @param CreateResource $request
     * @return mixed
     *      the created record, an asynchronous process, or a HTTP response.
     */
    protected function doCreate(StoreInterface $store, CreateResource $request)
    {
        if ($response = $this->beforeCommit($request)) {
            return $response;
        }

        $record = $store->createRecord(
            $request->getResourceType(),
            $request->all(),
            $request->getParameters()
        );

        return $this->afterCommit($request, $record, false) ?: $record;
    }

    /**
     * Update a resource.
     *
     * @param StoreInterface $store
     * @param UpdateResource $request
     * @return mixed
     *      the updated record, an asynchronous process, or a HTTP response.
     */
    protected function doUpdate(StoreInterface $store, UpdateResource $request)
    {
        if ($response = $this->beforeCommit($request)) {
            return $response;
        }

        $record = $store->updateRecord(
            $request->getRecord(),
            $request->all(),
            $request->getParameters()
        );

        return $this->afterCommit($request, $record, true) ?: $record;
    }

    /**
     * Delete a resource.
     *
     * @param StoreInterface $store
     * @param DeleteResource $request
     * @return mixed|null
     *      an HTTP response, an asynchronous process, content to return, or null.
     */
    protected function doDelete(StoreInterface $store, DeleteResource $request)
    {
        $record = $request->getRecord();

        if ($response = $this->invoke('deleting', $record, $request)) {
            return $response;
        }

        $result = $store->deleteRecord($record, $request->getParameters());

        return $this->invoke('deleted', $record, $request) ?: $result;
    }

    /**
     * Replace a relationship.
     *
     * @param StoreInterface $store
     * @param UpdateRelationship $request
     * @return mixed
     */
    protected function doReplaceRelationship(StoreInterface $store, UpdateRelationship $request)
    {
        $record = $request->getRecord();
        $name = Str::classify($field = $request->getRelationshipName());

        if ($result = $this->invokeMany(['replacing', "replacing{$name}"], $record, $request)) {
            return $result;
        }

        $record = $store->replaceRelationship(
            $record,
            $field,
            $request->all(),
            $request->getParameters()
        );

        return $this->invokeMany(["replaced{$name}", "replaced"], $record, $request) ?: $record;
    }

    /**
     * Add to a relationship.
     *
     * @param StoreInterface $store
     * @param UpdateRelationship $request
     * @return mixed
     */
    protected function doAddToRelationship(StoreInterface $store, UpdateRelationship $request)
    {
        $record = $request->getRecord();
        $name = Str::classify($field = $request->getRelationshipName());

        if ($result = $this->invokeMany(['adding', "adding{$name}"], $record, $request)) {
            return $result;
        }

        $record = $store->addToRelationship(
            $record,
            $field,
            $request->all(),
            $request->getParameters()
        );

        return $this->invokeMany(["added{$name}", "added"], $record, $request) ?: $record;
    }

    /**
     * Remove from a relationship.
     *
     * @param StoreInterface $store
     * @param UpdateRelationship $request
     * @return mixed
     */
    protected function doRemoveFromRelationship(StoreInterface $store, UpdateRelationship $request)
    {
        $record = $request->getRecord();
        $name = Str::classify($field = $request->getRelationshipName());

        if ($result = $this->invokeMany(['removing', "removing{$name}"], $record, $request)) {
            return $result;
        }

        $record = $store->removeFromRelationship(
            $record,
            $field,
            $request->all(),
            $request->getParameters()
        );

        return $this->invokeMany(["removed{$name}", "removed"], $record, $request) ?: $record;
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
     * Can the controller return the provided value?
     *
     * @param $value
     * @return bool
     */
    protected function isResponse($value)
    {
        return $value instanceof Response || $value instanceof Responsable;
    }

    /**
     * @param CreateResource|UpdateResource $request
     * @return mixed|null
     */
    private function beforeCommit($request)
    {
        $record = ($request instanceof UpdateResource) ? $request->getRecord() : null;

        if ($result = $this->invoke('saving', $record, $request)) {
            return $result;
        }

        return is_null($record) ?
            $this->invoke('creating', $request) :
            $this->invoke('updating', $record, $request);
    }

    /**
     * @param CreateResource|UpdateResource $request
     * @param $record
     * @param $updating
     * @return mixed|null
     */
    private function afterCommit($request, $record, $updating)
    {
        $method = !$updating ? 'created' : 'updated';

        if ($result = $this->invoke($method, $record, $request)) {
            return $result;
        }

        return $this->invoke('saved', $record, $request);
    }

    /**
     * @param $record
     * @param FetchRelated|FetchRelationship $request
     * @return mixed|null
     */
    private function beforeReadingRelationship($record, $request)
    {
        $field = Str::classify($request->getRelationshipName());
        $hooks = ['readingRelationship', "reading{$field}"];

        return $this->invokeMany($hooks, $record, $request);
    }

    /**
     * @param $record
     * @param $related
     *      the related resources that will be in the response.
     * @param FetchRelated|FetchRelationship $request
     * @return mixed|null
     */
    private function afterReadingRelationship($record, $related, $request)
    {
        $field = Str::classify($request->getRelationshipName());
        $hooks = ["didRead{$field}", 'didReadRelationship'];

        return $this->invokeMany($hooks, $record, $related, $request);
    }

    /**
     * @param $value
     * @return bool
     */
    protected function isInvokedResult($value): bool
    {
        return $value instanceof AsynchronousProcess || $this->isResponse($value);
    }

}
