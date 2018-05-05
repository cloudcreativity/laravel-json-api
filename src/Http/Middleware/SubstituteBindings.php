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

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\NotFoundException;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

class SubstituteBindings
{

    /**
     * @var StoreInterface
     */
    private $store;

    /**
     * @var RequestInterface
     */
    private $jsonApiRequest;

    /**
     * SubstituteBindings constructor.
     *
     * @param StoreInterface $store
     * @param RequestInterface $request
     */
    public function __construct(StoreInterface $store, RequestInterface $request)
    {
        $this->store = $store;
        $this->jsonApiRequest = $request;
    }

    /**
     * Substitute the JSON API binding.
     *
     * @param Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        if ($this->jsonApiRequest->getResourceId()) {
            $this->bind($request->route());
        }

        return $next($request);
    }

    /**
     * Bind the record to the route.
     *
     * @param Route $route
     * @return void
     */
    private function bind(Route $route)
    {
        if (!$record = $this->findRecord()) {
            throw new NotFoundException();
        }

        $route->setParameter(ResourceRegistrar::PARAM_RESOURCE_ID, $record);
    }

    /**
     * Check that the record exists.
     *
     * @return object|null
     */
    private function findRecord()
    {
        /** If the request is a read record request, we need to do this so eager loading occurs. */
        if ($this->jsonApiRequest->isReadResource()) {
            return $this->store->readRecord(
                $this->jsonApiRequest->getResourceType(),
                $this->jsonApiRequest->getResourceId(),
                $this->jsonApiRequest->getParameters()
            );
        }

        return $this->store->find($this->jsonApiRequest->getResourceIdentifier());
    }
}
