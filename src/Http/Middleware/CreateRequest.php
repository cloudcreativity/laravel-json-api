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
use CloudCreativity\JsonApi\Contracts\Http\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Object\ResourceIdentifier;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestHandlerInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RequestException;
use CloudCreativity\LaravelJsonApi\Http\Requests\JsonApiRequest;
use CloudCreativity\LaravelJsonApi\Services\JsonApiService;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;

/**
 * Class CreateRequest
 * @package CloudCreativity\LaravelJsonApi
 */
class CreateRequest
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
        /** @var RequestInterpreterInterface $interpreter */
        $interpreter = $this->container->make(RequestInterpreterInterface::class);
        /** @var JsonApiService $service */
        $service = $this->container->make(JsonApiService::class);

        /** Get the things we need to create a request */
        $resourceType = $handler->getResourceType();
        $resourceId = $interpreter->getResourceId();
        $record = $resourceId ? $this->record($resourceType, $resourceId) : null;

        /** Create the request and register it in the container prior to validation */
        $jsonApiRequest = new JsonApiRequest(
            $resourceType,
            $service->getEncodingParameters(),
            $resourceId,
            $interpreter->getRelationshipName(),
            $service->hasRequestDocument() ? $service->getRequestDocument() : null,
            $record
        );

        /** Register the request in the container so it is accessible after this point */
        $this->container->instance(JsonApiRequest::class, $jsonApiRequest);

        /** Ask the handler to validate the request */
        $handler->handle($interpreter, $jsonApiRequest);

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
            throw new RequestException("Class $requestClass is not a request handler.");
        }

        return $handler;
    }

    /**
     * @param $resourceType
     * @param $resourceId
     * @return object
     */
    private function record($resourceType, $resourceId)
    {
        /** @var StoreInterface $store */
        $store = $this->container->make(StoreInterface::class);
        $identifier = ResourceIdentifier::create($resourceType, $resourceId);

        return $store->find($identifier);
    }
}
