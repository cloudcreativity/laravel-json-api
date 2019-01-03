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

use Closure;
use CloudCreativity\LaravelJsonApi\Contracts\Auth\AuthorizerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use CloudCreativity\LaravelJsonApi\Http\Requests\JsonApiRequest;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

/**
 * Class AuthorizeRequest
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
     * @var JsonApiRequest
     */
    private $jsonApiRequest;

    /**
     * Authorize constructor.
     *
     * @param ContainerInterface $container
     * @param JsonApiRequest $request
     */
    public function __construct(ContainerInterface $container, JsonApiRequest $request)
    {
        $this->container = $container;
        $this->jsonApiRequest = $request;
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
        $this->authorizeRequest(
            $this->container->getAuthorizerByName($authorizer),
            $this->jsonApiRequest,
            $request
        );

        return $next($request);
    }

    /**
     * Authorize the request.
     *
     * @param AuthorizerInterface $authorizer
     * @param JsonApiRequest $jsonApiRequest
     * @param $request
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorizeRequest(AuthorizerInterface $authorizer, JsonApiRequest $jsonApiRequest, $request)
    {
        $type = $jsonApiRequest->getType();

        /** Index */
        if ($jsonApiRequest->isIndex()) {
            $authorizer->index($type, $request);
            return;
        } /** Create Resource */
        elseif ($jsonApiRequest->isCreateResource()) {
            $authorizer->create($type, $request);
            return;
        }

        $record = $jsonApiRequest->getResource();

        /** Read Resource */
        if ($jsonApiRequest->isReadResource()) {
            $authorizer->read($record, $request);
            return;
        } /** Update Resource */
        elseif ($jsonApiRequest->isUpdateResource()) {
            $authorizer->update($record, $request);
            return;
        } /** Delete Resource */
        elseif ($jsonApiRequest->isDeleteResource()) {
            $authorizer->delete($record, $request);
            return;
        }

        $field = $jsonApiRequest->getRelationshipName();

        /** Relationships */
        if ($jsonApiRequest->isReadRelatedResource() || $jsonApiRequest->isReadRelationship()) {
            $authorizer->readRelationship($record, $field, $request);
        } else {
            $authorizer->modifyRelationship($record, $field, $request);
        }
    }

}
