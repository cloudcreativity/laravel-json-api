<?php

/*
 * Copyright 2022 Cloud Creativity Limited
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
use CloudCreativity\LaravelJsonApi\Contracts\Auth\AuthorizerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Routing\Route;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

/**
 * Class Authorize
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Authorize
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var Route
     */
    private $route;

    /**
     * Authorize constructor.
     *
     * @param ContainerInterface $container
     * @param Route $route
     */
    public function __construct(ContainerInterface $container, Route $route)
    {
        $this->container = $container;
        $this->route = $route;
    }

    /**
     * Handle the request.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $authorizer
     * @return mixed
     * @throws AuthorizationException
     * @throws AuthenticationException
     */
    public function handle($request, Closure $next, $authorizer)
    {
        $authorizer = $this->container->getAuthorizerByName($authorizer);
        $record = $this->route->getResource();

        if ($field = $this->route->getRelationshipName()) {
            $this->authorizeRelationship(
                $authorizer,
                $request,
                $record,
                $field
            );
        } else if ($record) {
            $this->authorizeResource($authorizer, $request, $record);
        } else {
            $this->authorize($authorizer, $request, $this->route->getType());
        }

        return $next($request);
    }

    /**
     * @param AuthorizerInterface $authorizer
     * @param Request $request
     * @param string $type
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorize(AuthorizerInterface $authorizer, $request, string $type): void
    {
        if ($request->isMethod('POST')) {
            $authorizer->create($type, $request);
            return;
        }

        $authorizer->index($type, $request);
    }

    /**
     * @param AuthorizerInterface $authorizer
     * @param Request $request
     * @param $record
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorizeResource(AuthorizerInterface $authorizer, $request, $record): void
    {
        if ($request->isMethod('PATCH')) {
            $authorizer->update($record, $request);
            return;
        }

        if ($request->isMethod('DELETE')) {
            $authorizer->delete($record, $request);
            return;
        }

        $authorizer->read($record, $request);
    }

    /**
     * Authorize a relationship request.
     *
     * @param AuthorizerInterface $authorizer
     * @param $request
     * @param $record
     * @param string $field
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorizeRelationship(AuthorizerInterface $authorizer, $request, $record, string $field): void
    {
        if ($this->isModifyRelationship($request)) {
            $authorizer->modifyRelationship($record, $field, $request);
            return;
        }

        $authorizer->readRelationship($record, $field, $request);
    }

    /**
     * @param Request $request
     * @return bool
     */
    protected function isModifyRelationship($request): bool
    {
        return \in_array($request->getMethod(), ['POST', 'PATCH', 'DELETE']);
    }

}
