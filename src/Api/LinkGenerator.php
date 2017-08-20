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

namespace CloudCreativity\LaravelJsonApi\Api;

use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaFactoryInterface;
use Illuminate\Contracts\Routing\UrlGenerator as IlluminateUrlGenerator;

/**
 * Class LinkGenerator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class LinkGenerator
{

    /**
     * @var SchemaFactoryInterface
     */
    private $factory;

    /**
     * @var UrlGenerator
     */
    private $urls;

    /**
     * @var IlluminateUrlGenerator
     */
    private $generator;

    /**
     * LinkGenerator constructor.
     *
     * @param SchemaFactoryInterface $factory
     * @param UrlGenerator $urls
     * @param IlluminateUrlGenerator $generator
     */
    public function __construct(SchemaFactoryInterface $factory, UrlGenerator $urls, IlluminateUrlGenerator $generator)
    {
        $this->factory = $factory;
        $this->urls = $urls;
        $this->generator = $generator;
    }

    /**
     * Get a link to the current path, adding in supplied query params.
     *
     * @param array|object|null $meta
     * @param array $queryParams
     * @return LinkInterface
     */
    public function current($meta = null, array $queryParams = [])
    {
        $url = $this->generator->current();

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return $this->factory->createLink($url, $meta, true);
    }

    /**
     * Get a link to the index of a resource type.
     *
     * @param $resourceType
     * @param array|object|null $meta
     * @param array $queryParams
     * @return LinkInterface
     */
    public function index($resourceType, $meta = null, array $queryParams = [])
    {
        return $this->factory->createLink(
            $this->urls->index($resourceType, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to create a resource object.
     *
     * @param $resourceType
     * @param array|object|null $meta
     * @param array $queryParams
     * @return LinkInterface
     */
    public function create($resourceType, $meta = null, array $queryParams = [])
    {
        return $this->factory->createLink(
            $this->urls->create($resourceType, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to read a resource object.
     *
     * @param $resourceType
     * @param $id
     * @param array|object|null $meta
     * @param array $queryParams
     * @return LinkInterface
     */
    public function read($resourceType, $id, $meta = null, array $queryParams = [])
    {
        return $this->factory->createLink(
            $this->urls->read($resourceType, $id, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to update a resource object.
     *
     * @param $resourceType
     * @param $id
     * @param array|object|null $meta
     * @param array $queryParams
     * @return LinkInterface
     */
    public function update($resourceType, $id, $meta = null, array $queryParams = [])
    {
        return $this->factory->createLink(
            $this->urls->update($resourceType, $id, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to delete a resource object.
     *
     * @param $resourceType
     * @param $id
     * @param array|object|null $meta
     * @param array $queryParams
     * @return LinkInterface
     */
    public function delete($resourceType, $id, $meta = null, array $queryParams = [])
    {
        return $this->factory->createLink(
            $this->urls->delete($resourceType, $id, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to a resource object's related resource.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array|object|null $meta
     * @param array $queryParams
     * @return LinkInterface
     */
    public function relatedResource($resourceType, $id, $relationshipKey, $meta = null, array $queryParams = [])
    {
        return $this->factory->createLink(
            $this->urls->relatedResource($resourceType, $id, $relationshipKey, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to read a resource object's relationship.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array|object|null $meta
     * @param array $queryParams
     * @return LinkInterface
     */
    public function readRelationship($resourceType, $id, $relationshipKey, $meta = null, array $queryParams = [])
    {
        return $this->factory->createLink(
            $this->urls->readRelationship($resourceType, $id, $relationshipKey, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to replace a resource object's relationship.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array|object|null $meta
     * @param array $queryParams
     * @return LinkInterface
     */
    public function replaceRelationship($resourceType, $id, $relationshipKey, $meta = null, array $queryParams = [])
    {
        return $this->factory->createLink(
            $this->urls->replaceRelationship($resourceType, $id, $relationshipKey, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to add to a resource object's relationship.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array|object|null $meta
     * @param array $queryParams
     * @return LinkInterface
     */
    public function addRelationship($resourceType, $id, $relationshipKey, $meta = null, array $queryParams = [])
    {
        return $this->factory->createLink(
            $this->urls->addRelationship($resourceType, $id, $relationshipKey, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Get a link to remove from a resource object's relationship.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array|object|null $meta
     * @param array $queryParams
     * @return string
     */
    public function removeRelationship($resourceType, $id, $relationshipKey, $meta = null, array $queryParams = [])
    {
        return $this->factory->createLink(
            $this->urls->removeRelationship($resourceType, $id, $relationshipKey, $queryParams),
            $meta,
            true
        );
    }

}
