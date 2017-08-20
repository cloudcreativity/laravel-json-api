<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface as JsonApiRequest;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Class JsonApiController
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated
 */
class JsonApiController extends Controller
{

    use CreatesResponses;

    /**
     * @param JsonApiRequest $request
     * @return Response
     */
    public function index(JsonApiRequest $request)
    {
        return $this->notImplemented();
    }

    /**
     * @param JsonApiRequest $request
     * @return Response
     */
    public function create(JsonApiRequest $request)
    {
        return $this->notImplemented();
    }

    /**
     * @param JsonApiRequest $request
     * @return Response
     */
    public function read(JsonApiRequest $request)
    {
        return $this->notImplemented();
    }

    /**
     * @param JsonApiRequest $request
     * @return Response
     */
    public function update(JsonApiRequest $request)
    {
        return $this->notImplemented();
    }

    /**
     * @param JsonApiRequest $request
     * @return Response
     */
    public function delete(JsonApiRequest $request)
    {
        return $this->notImplemented();
    }

    /**
     * @param JsonApiRequest $request
     * @return Response
     */
    public function readRelatedResource(JsonApiRequest $request)
    {
        return $this->notImplemented();
    }

    /**
     * @param JsonApiRequest $request
     * @return Response
     */
    public function readRelationship(JsonApiRequest $request)
    {
        return $this->notImplemented();
    }

    /**
     * @param JsonApiRequest $request
     * @return Response
     */
    public function replaceRelationship(JsonApiRequest $request)
    {
        return $this->notImplemented();
    }

    /**
     * @param JsonApiRequest $request
     * @return Response
     */
    public function addToRelationship(JsonApiRequest $request)
    {
        return $this->notImplemented();
    }

    /**
     * @param JsonApiRequest $request
     * @return Response
     */
    public function removeFromRelationship(JsonApiRequest $request)
    {
        return $this->notImplemented();
    }

    /**
     * @return Response
     */
    protected function notImplemented()
    {
        return $this
            ->reply()
            ->statusCode(Response::HTTP_NOT_IMPLEMENTED);
    }

}
