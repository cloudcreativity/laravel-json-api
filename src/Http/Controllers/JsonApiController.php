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

namespace CloudCreativity\LaravelJsonApi\Http\Controllers;

use CloudCreativity\JsonApi\Contracts\Object\ResourceInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestHandlerInterface;
use CloudCreativity\LaravelJsonApi\Document\GeneratesLinks;
use CloudCreativity\LaravelJsonApi\Http\Responses\ReplyTrait;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;

/**
 * Class JsonApiController
 * @package CloudCreativity\LaravelJsonApi
 */
class JsonApiController extends Controller
{

    use ReplyTrait,
        GeneratesLinks;

    /**
     * @var RequestHandlerInterface
     */
    private $request;

    /**
     * JsonApiController constructor.
     * @param RequestHandlerInterface $request
     */
    public function __construct(RequestHandlerInterface $request)
    {
        $this->request = $request;
    }

    /**
     * @return Response
     */
    public function index()
    {
        return $this->notImplemented();
    }

    /**
     * @return Response
     */
    public function create()
    {
        return $this->notImplemented();
    }

    /**
     * @param $resourceId
     * @return Response
     */
    public function read($resourceId)
    {
        return $this->notImplemented();
    }

    /**
     * @param $resourceId
     * @return Response
     */
    public function update($resourceId)
    {
        return $this->notImplemented();
    }

    /**
     * @param $resourceId
     * @return Response
     */
    public function delete($resourceId)
    {
        return $this->notImplemented();
    }

    /**
     * @param $resourceId
     * @param $relationshipName
     * @return Response
     */
    public function readRelatedResource($resourceId, $relationshipName)
    {
        return $this->notImplemented();
    }

    /**
     * @param $resourceId
     * @param $relationshipName
     * @return Response
     */
    public function readRelationship($resourceId, $relationshipName)
    {
        return $this->notImplemented();
    }

    /**
     * @param $resourceId
     * @param $relationshipName
     * @return Response
     */
    public function replaceRelationship($resourceId, $relationshipName)
    {
        return $this->notImplemented();
    }

    /**
     * @param $resourceId
     * @param $relationshipName
     * @return Response
     */
    public function addToRelationship($resourceId, $relationshipName)
    {
        return $this->notImplemented();
    }

    /**
     * @param $resourceId
     * @param $relationshipName
     * @return Response
     */
    public function removeFromRelationship($resourceId, $relationshipName)
    {
        return $this->notImplemented();
    }

    /**
     * @return RequestHandlerInterface
     */
    protected function getRequestHandler()
    {
        return $this->request;
    }

    /**
     * Shorthand to get the record that the request relates to.
     *
     * @return object
     */
    protected function getRecord()
    {
        return $this->request->getRecord();
    }

    /**
     * Shorthand to get the resource that the client has submitted.
     *
     * @return ResourceInterface
     */
    protected function getResource()
    {
        return $this->request->getDocument()->getResource();
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
