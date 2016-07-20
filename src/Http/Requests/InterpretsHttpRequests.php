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

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Http\Request as HttpRequest;

/**
 * Class InterpretsHttpRequests
 * @package CloudCreativity\LaravelJsonApi
 */
trait InterpretsHttpRequests
{

    /**
     * @return HttpRequest
     */
    abstract public function getHttpRequest();

    /**
     * What resource id was sent by the client?
     *
     * @return string|null
     */
    public function getResourceId()
    {
        return $this->getHttpRequest()->route(ResourceRegistrar::PARAM_RESOURCE_ID);
    }

    /**
     * What relationship name was sent by the client?
     *
     * @return string|null
     */
    public function getRelationshipName()
    {
        return $this->getHttpRequest()->route(ResourceRegistrar::PARAM_RELATIONSHIP_NAME);
    }

    /**
     * Is this an index request?
     *
     * E.g. `GET /posts`
     *
     * @return bool
     */
    public function isIndex()
    {
        return $this->getHttpRequest()->isMethod('get') && !$this->isResource();
    }

    /**
     * Is this a create resource request?
     *
     * E.g. `POST /posts`
     *
     * @return bool
     */
    public function isCreateResource()
    {
        return $this->getHttpRequest()->isMethod('post') && !$this->isResource();
    }

    /**
     * Is this a read resource request?
     *
     * E.g. `GET /posts/1`
     *
     * @return bool
     */
    public function isReadResource()
    {
        return $this->getHttpRequest()->isMethod('get') && $this->isResource() && !$this->isRelationship();
    }

    /**
     * Is this an update resource request?
     *
     * E.g. `PATCH /posts/1`
     *
     * @return bool
     */
    public function isUpdateResource()
    {
        return $this->getHttpRequest()->isMethod('patch') && $this->isResource() && !$this->isRelationship();
    }

    /**
     * Is this a delete resource request?
     *
     * E.g. `DELETE /posts/1`
     *
     * @return bool
     */
    public function isDeleteResource()
    {
        return $this->getHttpRequest()->isMethod('delete') && $this->isResource() && !$this->isRelationship();
    }

    /**
     * Is this a request for a related resource or resources?
     *
     * E.g. `GET /posts/1/author` or `GET /posts/1/comments`
     *
     * @return bool
     */
    public function isReadRelatedResource()
    {
        return $this->isRelationship() && !$this->isRelationshipData();
    }

    /**
     * Is this a request to read the data of a relationship?
     *
     * E.g. `GET /posts/1/relationships/author` or `GET /posts/1/relationships/comments`
     *
     * @return bool
     */
    public function isReadRelationship()
    {
        return $this->getHttpRequest()->isMethod('get') && $this->isRelationshipData();
    }

    /**
     * Is this a request to modify the data of a relationship?
     *
     * @return bool
     */
    public function isModifyRelationship()
    {
        return $this->isReplaceRelationship() ||
            $this->isAddToRelationship() ||
            $this->isRemoveFromRelationship();
    }

    /**
     * Is this a request to replace the data of a relationship?
     *
     * E.g. `PATCH /posts/1/relationships/author` or `PATCH /posts/1/relationships/comments`
     */
    public function isReplaceRelationship()
    {
        return $this->getHttpRequest()->isMethod('patch') && $this->isRelationshipData();
    }

    /**
     * Is this a request to add to the data of a has-many relationship?
     *
     * E.g. `POST /posts/1/relationships/comments`
     *
     * @return bool
     */
    public function isAddToRelationship()
    {
        return $this->getHttpRequest()->isMethod('post') && $this->isRelationshipData();
    }

    /**
     * Is this a request to remove from the data of a has-many relationship?
     *
     * E.g. `DELETE /posts/1/relationships/comments`
     *
     * @return bool
     */
    public function isRemoveFromRelationship()
    {
        return $this->getHttpRequest()->isMethod('delete') && $this->isRelationshipData();
    }

    /**
     * @return bool
     */
    public function isResource()
    {
        return !empty($this->getResourceId());
    }

    /**
     * Does the request URL have a relationship name in it?
     *
     * @return bool
     */
    public function isRelationship()
    {
        return !empty($this->getRelationshipName());
    }

    /**
     * Is this a request for relationship data?
     *
     * E.g. `/posts/1/relationships/author` or `/posts/1/relationships/comments`
     *
     * @return bool
     * @see http://jsonapi.org/format/#fetching-relationships
     */
    public function isRelationshipData()
    {
        return $this->isRelationship() && $this->getHttpRequest()->is('*/relationships/*');
    }

    /**
     * Is this a request where we expect a document to be sent by the client?
     *
     * @return bool
     */
    public function isExpectingDocument()
    {
        return $this->isCreateResource() ||
            $this->isUpdateResource() ||
            $this->isReplaceRelationship() ||
            $this->isAddToRelationship() ||
            $this->isRemoveFromRelationship();
    }
}
