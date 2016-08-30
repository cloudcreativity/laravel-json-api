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

namespace CloudCreativity\LaravelJsonApi\Http\Middleware;

use Closure;
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestHandlerInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

/**
 * Class CreateRequest
 * @package CloudCreativity\LaravelJsonApi
 */
class HandleRequest
{

    /**
     * @var Container
     */
    private $container;

    /**
     * ValidateRequest constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @param $requestClass
     * @return mixed
     */
    public function handle($request, Closure $next, $requestClass)
    {
        $handler = $this->requestHandler($requestClass);
        /** @var JsonApiService $service */
        $service = $this->container->make(JsonApiService::class);

        /** Ask the handler to validate the request */
        $handler->handle($service->getApi(), $service->getRequest());

        return $next($request);
    }

    /**
     * @param $requestClass
     * @return RequestHandlerInterface
     */
    private function requestHandler($requestClass)
    {
        $handler = $this->container->make($requestClass);

        if (!$handler instanceof RequestHandlerInterface) {
            throw new RuntimeException("Class $requestClass is not a request handler.");
        }

        return $handler;
    }

}
