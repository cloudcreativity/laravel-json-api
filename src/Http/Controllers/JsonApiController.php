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
use CloudCreativity\LaravelJsonApi\Auth\AuthorizesRequests;
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
use CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class JsonApiController
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiController extends Controller
{

    use CreatesResponses, AuthorizesRequests;

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

        if ($result instanceof Response) {
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
        $record = $store->readRecord(
            $request->getResourceType(),
            $request->getResourceId(),
            $request->getParameters()
        );

        if ($record && $result = $this->invoke('reading', $record, $request)) {
            return $result;
        }

        return $this->reply()->content($record);
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

        if ($record instanceof Response) {
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

        if ($record instanceof Response) {
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

        if ($result instanceof Response) {
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

        if ($result = $this->beforeReadingRelationship($record, $request)) {
            return $result;
        }

        $related = $store->queryRelated(
            $record,
            $request->getRelationshipName(),
            $request->getParameters()
        );

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

        if ($result = $this->beforeReadingRelationship($record, $request)) {
            return $result;
        }

        $related = $store->queryRelationship(
            $record,
            $request->getRelationshipName(),
            $request->getParameters()
        );

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

        if ($result instanceof Response) {
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

        if ($result instanceof Response) {
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

        if ($result instanceof Response) {
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
            $request->getProcessType(),
            $request->getProcessId(),
            $request->getEncodingParameters()
        );

        return $this->reply()->process($record);
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
        if ($result = $this->invoke('searching', $request)) {
            return $result;
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
            $request->all(),
            $request->getParameters()
        );

        return $this->afterCommit($request, $record, true) ?: $record;
    }

    /**
     * Delete a resource.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response|mixed|null
     *      an HTTP response, content to return or null.
     */
    protected function doDelete(StoreInterface $store, ValidatedRequest $request)
    {
        $record = $request->getRecord();
        $response = $this->invoke('deleting', $record, $request);

        if ($response instanceof Response) {
            return $response;
        }

        $result = $store->deleteRecord($record, $request->getParameters());

        return $this->invoke('deleted', $record, $request) ?: $result;
    }

    /**
     * Replace a relationship.
     *
     * @param StoreInterface $store
     * @param ValidatedRequest $request
     * @return Response|object
     */
    protected function doReplaceRelationship(StoreInterface $store, ValidatedRequest $request)
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
     * @param ValidatedRequest $request
     * @return Response|object
     */
    protected function doAddToRelationship(StoreInterface $store, ValidatedRequest $request)
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
     * @param ValidatedRequest $request
     * @return Response|object
     */
    protected function doRemoveFromRelationship(StoreInterface $store, ValidatedRequest $request)
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
     * @param ValidatedRequest $request
     * @return Response|null
     */
    private function beforeCommit(ValidatedRequest $request)
    {
        $record = $request->getRecord();

        if ($result = $this->invoke('saving', $record, $request)) {
            return $result;
        }

        return is_null($record) ?
            $this->invoke('creating', $request) :
            $this->invoke('updating', $record, $request);
    }

    /**
     * @param ValidatedRequest $request
     * @param $record
     * @param $updating
     * @return Response|null
     */
    private function afterCommit(ValidatedRequest $request, $record, $updating)
    {
        $method = !$updating ? 'created' : 'updated';

        if ($result = $this->invoke($method, $record, $request)) {
            return $result;
        }

        return $this->invoke('saved', $record, $request);
    }

    /**
     * @param $record
     * @param ValidatedRequest $request
     * @return Response|null
     */
    private function beforeReadingRelationship($record, ValidatedRequest $request)
    {
        $name = Str::classify($relationship = $request->getRelationshipName());
        $hooks = ['readingRelationship', "reading{$name}"];

        return $this->invokeMany($hooks, $record, $request);
    }

    /**
     * Invoke a hook.
     *
     * @param $method
     * @param mixed ...$arguments
     * @return Response|null
     */
    private function invoke($method, ...$arguments)
    {
        $response = method_exists($this, $method) ? $this->{$method}(...$arguments) : null;

        return ($response instanceof Response) ? $response : null;
    }

    /**
     * Invoke multiple hooks.
     *
     * @param array $method
     * @param mixed ...$arguments
     * @return Response|null
     */
    private function invokeMany(array $method, ...$arguments)
    {
        foreach ($method as $hook) {
            $result = $this->invoke($hook, ...$arguments);

            if ($result instanceof Response) {
                return $result;
            }
        }

        return null;
    }

}
