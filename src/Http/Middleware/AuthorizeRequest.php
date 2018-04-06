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

use Closure;
use CloudCreativity\LaravelJsonApi\Contracts\Authorizer\AuthorizerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\InboundRequestInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\AuthorizationException;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Http\Request;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * Class AuthorizeRequest
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class AuthorizeRequest
{

    /**
     * @var Container
     */
    protected $container;

    /**
     * AuthorizeRequest constructor.
     *
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param Request $request
     * @param Closure $next
     * @param string $authorizer
     * @return mixed
     */
    public function handle($request, Closure $next, $authorizer)
    {
        $this->authorize(
            $this->container->make(InboundRequestInterface::class),
            $this->container->make(StoreInterface::class),
            $this->resolveAuthorizer($authorizer)
        );

        return $next($request);
    }

    /**
     * @param $name
     * @return AuthorizerInterface
     */
    protected function resolveAuthorizer($name)
    {
        $authorizer = $this->container->make($name);

        if (!$authorizer instanceof AuthorizerInterface) {
            throw new RuntimeException("Authorizer '$name' is not an authorizer instance.");
        }

        return $authorizer;
    }


    /**
     * Authorize the request or throw an exception
     *
     * @param InboundRequestInterface $request
     * @param StoreInterface $store
     * @param AuthorizerInterface $authorizer
     * @throws AuthorizationException
     */
    protected function authorize(
        InboundRequestInterface $request,
        StoreInterface $store,
        AuthorizerInterface $authorizer
    ) {
        $result = $this->checkAuthorization($request, $store, $authorizer);

        if (true !== $result) {
            throw new AuthorizationException($result);
        }
    }

    /**
     * @param InboundRequestInterface $request
     * @param StoreInterface $store
     * @param AuthorizerInterface $authorizer
     * @return ErrorCollection|bool
     *      errors if the request is not authorized, true if authorized.
     */
    protected function checkAuthorization(
        InboundRequestInterface $request,
        StoreInterface $store,
        AuthorizerInterface $authorizer
    ) {
        $parameters = $request->getParameters();
        $document = $request->getDocument();
        $identifier = $request->getResourceIdentifier();
        $record = $identifier ? $store->findOrFail($identifier) : null;
        $authorized = true;

        /** Index */
        if ($request->isIndex()) {
            $authorized = $authorizer->canReadMany($request->getResourceType(), $parameters);
        } /** Create Resource */
        elseif ($request->isCreateResource()) {
            $authorized = $authorizer->canCreate($request->getResourceType(), $document->getResource(), $parameters);
        } /** Read Resource */
        elseif ($request->isReadResource()) {
            $authorized = $authorizer->canRead($record, $parameters);
        } /** Update Resource */
        elseif ($request->isUpdateResource()) {
            $authorized = $authorizer->canUpdate($record, $document->getResource(), $parameters);
        } /** Delete Resource */
        elseif ($request->isDeleteResource()) {
            $authorized = $authorizer->canDelete($record, $parameters);
        } /** Read Related Resource */
        elseif ($request->isReadRelatedResource()) {
            $authorized = $authorizer->canReadRelatedResource(
                $request->getRelationshipName(),
                $record,
                $parameters
            );
        } /** Read Relationship Data */
        elseif ($request->isReadRelationship()) {
            $authorized = $authorizer->canReadRelationship(
                $request->getRelationshipName(),
                $record,
                $parameters
            );
        } /** Modify Relationship Data */
        elseif ($request->isModifyRelationship()) {
            $authorized = $authorizer->canModifyRelationship(
                $request->getRelationshipName(),
                $record,
                $document->getRelationship(),
                $parameters
            );
        }

        return $authorized ?: $authorizer->getErrors();
    }
}
