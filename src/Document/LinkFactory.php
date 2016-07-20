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

namespace CloudCreativity\LaravelJsonApi\Document;

use CloudCreativity\LaravelJsonApi\Contracts\Document\LinkFactoryInterface;
use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use Illuminate\Contracts\Routing\UrlGenerator;
use Neomerx\JsonApi\Contracts\Document\LinkInterface;
use Neomerx\JsonApi\Document\Link;

/**
 * Class LinkFactory
 * @package CloudCreativity\LaravelJsonApi
 */
class LinkFactory implements LinkFactoryInterface
{

    use GeneratesRouteNames;

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
     * @param array $queryParams
     * @param array|object|null $meta
     * @return Link
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
     * Get a link to the index of a resource type.
     *
     * @param $resourceType
     * @param array $queryParams
     * @param array|object|null
     *      meta to attach to the link object.
     * @return LinkInterface
     */
    public function index(
        $resourceType,
        array $queryParams = [],
        $meta = null
    ) {
        $name = $this->indexRouteName($resourceType);

        return $this->route($name, $queryParams, $meta);
    }

    /**
     * Get a link to a resource object.
     *
     * @param $resourceType
     * @param $id
     * @param array $queryParams
     * @param array|object|null
     *      meta to attach to the link object.
     * @return LinkInterface
     */
    public function resource(
        $resourceType,
        $id,
        array $queryParams = [],
        $meta = null
    ) {
        $queryParams[ResourceRegistrar::PARAM_RESOURCE_ID] = $id;
        $name = $this->resourceRouteName($resourceType);

        return $this->route($name, $queryParams, $meta);
    }

    /**
     * Get a link to a resource object's related resource.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array $queryParams
     * @param array|object|null
     *      meta to attach to the link object.
     * @return LinkInterface
     */
    public function relatedResource(
        $resourceType,
        $id,
        $relationshipKey,
        array $queryParams = [],
        $meta = null
    ) {
        $queryParams[ResourceRegistrar::PARAM_RESOURCE_ID] = $id;
        $queryParams[ResourceRegistrar::PARAM_RELATIONSHIP_NAME] = $relationshipKey;
        $name = $this->relatedResourceRouteName($resourceType);

        return $this->route($name, $queryParams, $meta);
    }

    /**
     * Get a link to a resource object's relationship.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array $queryParams
     * @param array|object|null
     *      meta to attach to the link object.
     * @return LinkInterface
     */
    public function relationship(
        $resourceType,
        $id,
        $relationshipKey,
        array $queryParams = [],
        $meta = null
    ) {
        $queryParams[ResourceRegistrar::PARAM_RESOURCE_ID] = $id;
        $queryParams[ResourceRegistrar::PARAM_RELATIONSHIP_NAME] = $relationshipKey;
        $name = $this->relationshipRouteName($resourceType);

        return $this->route($name, $queryParams, $meta);
    }

    /**
     * Get a JSON API link to a named route within your application.
     *
     * @param $name
     * @param array $parameters
     * @param array|object|null
     *      meta to attach to the link object.
     * @return LinkInterface
     */
    public function route($name, $parameters = [], $meta = null)
    {
        $uri = $this->generator->route($name, $parameters);

        return new Link($uri, $meta, true);
    }

}
