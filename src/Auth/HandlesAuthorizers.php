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

namespace CloudCreativity\LaravelJsonApi\Auth;

use CloudCreativity\LaravelJsonApi\Contracts\Auth\AuthorizerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;

/**
 * Trait UsesAuthorizers
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait HandlesAuthorizers
{

    /**
     * Authorize the request.
     *
     * @param AuthorizerInterface $authorizer
     * @param RequestInterface $jsonApiRequest
     * @param $request
     * @throws AuthenticationException
     * @throws AuthorizationException
     */
    protected function authorizeRequest(AuthorizerInterface $authorizer, RequestInterface $jsonApiRequest, $request)
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
