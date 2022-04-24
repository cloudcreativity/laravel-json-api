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

namespace CloudCreativity\LaravelJsonApi\Api;

use Illuminate\Contracts\Routing\UrlGenerator as IlluminateUrlGenerator;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;

/**
 * Class LinkGenerator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class LinkGenerator
{

    /**
     * @var FactoryInterface
     */
    private FactoryInterface $factory;

    /**
     * @var UrlGenerator
     */
    private UrlGenerator $urls;

    /**
     * @var IlluminateUrlGenerator
     */
    private IlluminateUrlGenerator $generator;

    /**
     * LinkGenerator constructor.
     *
     * @param FactoryInterface $factory
     * @param UrlGenerator $urls
     * @param IlluminateUrlGenerator $generator
     */
    public function __construct(FactoryInterface $factory, UrlGenerator $urls, IlluminateUrlGenerator $generator)
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

        return $this->createLink($url, $meta, true);
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
        return $this->createLink(
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
        return $this->createLink(
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
        return $this->createLink(
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
        return $this->createLink(
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
        return $this->createLink(
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
        return $this->createLink(
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
        return $this->createLink(
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
        return $this->createLink(
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
        return $this->createLink(
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
     * @return LinkInterface
     */
    public function removeRelationship($resourceType, $id, $relationshipKey, $meta = null, array $queryParams = [])
    {
        return $this->createLink(
            $this->urls->removeRelationship($resourceType, $id, $relationshipKey, $queryParams),
            $meta,
            true
        );
    }

    /**
     * Create a link.
     *
     * This method uses the old method signature for creating a link via the Neomerx factory, and converts
     * it to a call to the new factory method signature.
     *
     * @param string $subHref
     * @param array|object|null $meta
     * @param bool $treatAsHref
     * @return LinkInterface
     */
    private function createLink(string $subHref, $meta = null, bool $treatAsHref = false): LinkInterface
    {
        return $this->factory->createLink(
            false === $treatAsHref,
            $subHref,
            !is_null($meta),
            $meta,
        );
    }
}
