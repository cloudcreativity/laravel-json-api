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

namespace CloudCreativity\JsonApi\Contracts\Resolver;

/**
 * Interface ResolverInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface ResolverInterface
{

    /**
     * Does the supplied domain record type exist?
     *
     * @param string $type
     * @return bool
     */
    public function isType($type);

    /**
     * Get the domain record type for the supplied JSON API resource type.
     *
     * @param string $resourceType
     * @return string|null
     */
    public function getType($resourceType);

    /**
     * Get all domain record types.
     *
     * @return string[]
     */
    public function getAllTypes();

    /**
     * Does the supplied JSON API resource type exist?
     *
     * @param $resourceType
     * @return bool
     */
    public function isResourceType($resourceType);

    /**
     * Get the JSON API resource type for the supplied domain record type.
     *
     * @param string $type
     * @return string|null
     */
    public function getResourceType($type);

    /**
     * Get all JSON API resource types.
     *
     * @return string[]
     */
    public function getAllResourceTypes();

    /**
     * Get schema by domain record type.
     *
     * @param $type
     * @return string|null
     */
    public function getSchemaByType($type);

    /**
     * Get schema by JSON API resource type.
     *
     * @param $resourceType
     * @return string
     */
    public function getSchemaByResourceType($resourceType);

    /**
     * Get adapter by domain record type.
     *
     * @param $type
     * @return string|null
     */
    public function getAdapterByType($type);

    /**
     * Get adapter by JSON API resource type.
     *
     * @param $resourceType
     * @return string
     */
    public function getAdapterByResourceType($resourceType);

    /**
     * @param $type
     * @return string|null
     */
    public function getAuthorizerByType($type);

    /**
     * @param $resourceType
     * @return string
     */
    public function getAuthorizerByResourceType($resourceType);

    /**
     * @param $type
     * @return string|null
     */
    public function getValidatorsByType($type);

    /**
     * @param $resourceType
     * @return string
     */
    public function getValidatorsByResourceType($resourceType);
}
