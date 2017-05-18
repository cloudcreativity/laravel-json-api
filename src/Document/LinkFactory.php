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

namespace CloudCreativity\LaravelJsonApi\Document;

use CloudCreativity\LaravelJsonApi\Contracts\Document\LinkFactoryInterface;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use CloudCreativity\LaravelJsonApi\Routing\RouteName;
use Illuminate\Contracts\Routing\UrlGenerator;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Document\Link;

/**
 * Class LinkFactory
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class LinkFactory implements LinkFactoryInterface
{

    /**
     * @var UrlGenerator
     */
    private $generator;

    /**
     * LinkFactory constructor.
     * @param UrlGenerator $generator
     */
    public function __construct(UrlGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * @inheritdoc
     */
    public function current(array $queryParams = [], $meta = null)
    {
        $url = $this->generator->current();

        if ($queryParams) {
            $url .= '?' . http_build_query($queryParams);
        }

        return new Link($url, $meta, true);
    }

    /**
     * @inheritdoc
     */
    public function create($resourceType, array $queryParams = [], $meta = null)
    {
        return $this->route(RouteName::create($resourceType), $queryParams, $meta);
    }

    /**
     * @inheritdoc
     */
    public function index($resourceType, array $queryParams = [], $meta = null)
    {
        return $this->route(RouteName::index($resourceType), $queryParams, $meta);
    }

    /**
     * @inheritdoc
     */
    public function read($resourceType, $id, array $queryParams = [], $meta = null)
    {
        $queryParams[ResourceRegistrar::PARAM_RESOURCE_ID] = $id;
        $name = RouteName::read($resourceType);

        return $this->route($name, $queryParams, $meta);
    }

    /**
     * @inheritDoc
     */
    public function update($resourceType, $id, array $queryParams = [], $meta = null)
    {
        $queryParams[ResourceRegistrar::PARAM_RESOURCE_ID] = $id;
        $name = RouteName::update($resourceType);

        return $this->route($name, $queryParams, $meta);
    }

    /**
     * @inheritDoc
     */
    public function delete($resourceType, $id, array $queryParams = [], $meta = null)
    {
        $queryParams[ResourceRegistrar::PARAM_RESOURCE_ID] = $id;
        $name = RouteName::delete($resourceType);

        return $this->route($name, $queryParams, $meta);
    }

    /**
     * @inheritdoc
     */
    public function relatedResource(
        $resourceType,
        $id,
        $relationshipKey,
        array $queryParams = [],
        $meta = null
    ) {
        $name = RouteName::related($resourceType, $relationshipKey);

        return $this->relationshipRoute($name, $id, $relationshipKey, $queryParams, $meta);
    }

    /**
     * @inheritdoc
     */
    public function readRelationship(
        $resourceType,
        $id,
        $relationshipKey,
        array $queryParams = [],
        $meta = null
    ) {
        $name = RouteName::readRelationship($resourceType, $relationshipKey);

        return $this->relationshipRoute($name, $id, $relationshipKey, $queryParams, $meta);
    }

    /**
     * @inheritdoc
     */
    public function replaceRelationship(
        $resourceType,
        $id,
        $relationshipKey,
        array $queryParams = [],
        $meta = null
    ) {
        $name = RouteName::replaceRelationship($resourceType, $relationshipKey);

        return $this->relationshipRoute($name, $id, $relationshipKey, $queryParams, $meta);
    }

    /**
     * @inheritdoc
     */
    public function addRelationship(
        $resourceType,
        $id,
        $relationshipKey,
        array $queryParams = [],
        $meta = null
    ) {
        $name = RouteName::addRelationship($resourceType, $relationshipKey);

        return $this->relationshipRoute($name, $id, $relationshipKey, $queryParams, $meta);
    }

    /**
     * @inheritdoc
     */
    public function removeRelationship(
        $resourceType,
        $id,
        $relationshipKey,
        array $queryParams = [],
        $meta = null
    ) {
        $name = RouteName::removeRelationship($resourceType, $relationshipKey);

        return $this->relationshipRoute($name, $id, $relationshipKey, $queryParams, $meta);
    }

    /**
     * @inheritdoc
     */
    public function route($name, $parameters = [], $meta = null)
    {
        $uri = $this->generator->route($name, $parameters);

        return new Link($uri, $meta, true);
    }

    /**
     * @param $name
     * @param $id
     * @param $relationshipKey
     * @param array $queryParams
     * @param $meta
     * @return LinkInterface|Link
     */
    private function relationshipRoute($name, $id, $relationshipKey, array $queryParams, $meta)
    {
        $queryParams[ResourceRegistrar::PARAM_RESOURCE_ID] = $id;
        $queryParams[ResourceRegistrar::PARAM_RELATIONSHIP_NAME] = $relationshipKey;

        return $this->route($name, $queryParams, $meta);
    }

}
