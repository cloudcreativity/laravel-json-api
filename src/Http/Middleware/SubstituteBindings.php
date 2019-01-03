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

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\NotFoundException;
use CloudCreativity\LaravelJsonApi\Http\Requests\JsonApiRequest;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;

/**
 * Class SubstituteBindings
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class SubstituteBindings
{

    /**
     * @var StoreInterface
     */
    private $store;

    /**
     * @var JsonApiRequest
     */
    private $jsonApiRequest;

    /**
     * SubstituteBindings constructor.
     *
     * @param StoreInterface $store
     * @param JsonApiRequest $request
     */
    public function __construct(StoreInterface $store, JsonApiRequest $request)
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
            $this->bindResource($request->route());
        }

        if ($this->jsonApiRequest->getProcessId()) {
            $this->bindProcess($request->route());
        }

        return $next($request);
    }

    /**
     * Bind the record to the route.
     *
     * @param Route $route
     * @return void
     */
    private function bindResource(Route $route): void
    {
        $record = $this->store->find($this->jsonApiRequest->getResourceIdentifier());

        if (!$record) {
            throw new NotFoundException();
        }

        $route->setParameter(ResourceRegistrar::PARAM_RESOURCE_ID, $record);
    }

    /**
     * Bind the process to the route.
     *
     * @param Route $route
     * @return void
     */
    private function bindProcess(Route $route): void
    {
        $process = $this->store->find($this->jsonApiRequest->getProcessIdentifier());

        if (!$process) {
            throw new NotFoundException();
        }

        $route->setParameter(ResourceRegistrar::PARAM_PROCESS_ID, $process);
    }

}
