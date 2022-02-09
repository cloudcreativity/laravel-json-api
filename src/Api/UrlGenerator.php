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

use CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar;
use CloudCreativity\LaravelJsonApi\Routing\RouteName;
use Illuminate\Contracts\Routing\UrlGenerator as IlluminateUrlGenerator;

/**
 * Class UrlGenerator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class UrlGenerator
{

    /**
     * @var IlluminateUrlGenerator
     */
    private $generator;

    /**
     * @var Url
     */
    private $url;

    /**
     * UrlGenerator constructor.
     *
     * @param IlluminateUrlGenerator $generator
     * @param Url $url
     */
    public function __construct(IlluminateUrlGenerator $generator, Url $url)
    {
        $this->generator = $generator;
        $this->url = $url;
    }

    /**
     * Get a link to the index of a resource type.
     *
     * @param $resourceType
     * @param array $queryParams
     * @return string
     */
    public function index($resourceType, array $queryParams = [])
    {
        return $this->route(RouteName::index($resourceType), $queryParams);
    }

    /**
     * Get a link to create a resource object.
     *
     * @param $resourceType
     * @param array $queryParams
     * @return string
     */
    public function create($resourceType, array $queryParams = [])
    {
        return $this->route(RouteName::create($resourceType), $queryParams);
    }

    /**
     * Get a link to read a resource object.
     *
     * @param $resourceType
     * @param $id
     * @param array $queryParams
     * @return string
     */
    public function read($resourceType, $id, array $queryParams = [])
    {
        return $this->resource(RouteName::read($resourceType), $id, $queryParams);
    }

    /**
     * Get a link to update a resource object.
     *
     * @param $resourceType
     * @param $id
     * @param array $queryParams
     * @return string
     */
    public function update($resourceType, $id, array $queryParams = [])
    {
        return $this->resource(RouteName::update($resourceType), $id, $queryParams);
    }

    /**
     * Get a link to delete a resource object.
     *
     * @param $resourceType
     * @param $id
     * @param array $queryParams
     * @return string
     */
    public function delete($resourceType, $id, array $queryParams = [])
    {
        return $this->resource(RouteName::delete($resourceType), $id, $queryParams);
    }

    /**
     * Get a link to a resource object's related resource.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array $queryParams
     * @return string
     */
    public function relatedResource($resourceType, $id, $relationshipKey, array $queryParams = [])
    {
        return $this->resource(RouteName::related($resourceType, $relationshipKey), $id, $queryParams);
    }

    /**
     * Get a link to read a resource object's relationship.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array $queryParams
     * @return string
     */
    public function readRelationship($resourceType, $id, $relationshipKey, array $queryParams = [])
    {
        $name = RouteName::readRelationship($resourceType, $relationshipKey);

        return $this->resource($name, $id, $queryParams);
    }

    /**
     * Get a link to replace a resource object's relationship.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array $queryParams
     * @return string
     */
    public function replaceRelationship($resourceType, $id, $relationshipKey, array $queryParams = [])
    {
        $name = RouteName::replaceRelationship($resourceType, $relationshipKey);

        return $this->resource($name, $id, $queryParams);
    }

    /**
     * Get a link to add to a resource object's relationship.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array $queryParams
     * @return string
     */
    public function addRelationship($resourceType, $id, $relationshipKey, array $queryParams = [])
    {
        $name = RouteName::addRelationship($resourceType, $relationshipKey);

        return $this->resource($name, $id, $queryParams);
    }

    /**
     * Get a link to remove from a resource object's relationship.
     *
     * @param $resourceType
     * @param $id
     * @param $relationshipKey
     * @param array $queryParams
     * @return string
     */
    public function removeRelationship($resourceType, $id, $relationshipKey, array $queryParams = [])
    {
        $name = RouteName::removeRelationship($resourceType, $relationshipKey);

        return $this->resource($name, $id, $queryParams);
    }

    /**
     * @param $name
     * @param array $parameters
     * @return string
     */
    private function route($name, $parameters = [])
    {
        $name = $this->url->getName() . $name;

        return $this->generator->route($name, $parameters, true);
    }

    /**
     * @param $name
     * @param $id
     * @param array $parameters
     * @return string
     */
    private function resource($name, $id, $parameters = [])
    {
        $parameters[ResourceRegistrar::PARAM_RESOURCE_ID] = $id;

        return $this->route($name, $parameters);
    }
}
