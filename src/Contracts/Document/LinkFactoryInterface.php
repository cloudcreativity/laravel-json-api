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

namespace CloudCreativity\LaravelJsonApi\Contracts\Document;

use Neomerx\JsonApi\Contracts\Document\LinkInterface;

/**
 * Interface LinkFactoryInterface
 * @package CloudCreativity\LaravelJsonApi
 */
interface LinkFactoryInterface
{

    /**
     * Get a link to the current URL.
     *
     * @param array $queryParams
     * @param array|object|null $meta
     *      meta to attach to the link object.
     * @return LinkInterface
     */
    public function current(array $queryParams = [], $meta = null);

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
    );

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
    );

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
    );

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
    );

    /**
     * Get a JSON API link to a named route within your application.
     *
     * @param $name
     * @param array $parameters
     * @param array|object|null
     *      meta to attach to the link object.
     * @return LinkInterface
     */
    public function route($name, $parameters = [], $meta = null);
}
