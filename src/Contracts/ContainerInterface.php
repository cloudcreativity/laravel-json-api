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

namespace CloudCreativity\LaravelJsonApi\Contracts;

use CloudCreativity\LaravelJsonApi\Contracts\Adapter\ResourceAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Auth\AuthorizerInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\ContentNegotiatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Schema\SchemaProviderInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorFactoryInterface;

/**
 * Interface ContainerInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface ContainerInterface
{
    /**
     * Get schema provider for resource object.
     *
     * @param object $resourceObject
     * @return SchemaProviderInterface
     */
    public function getSchema(object $resourceObject): SchemaProviderInterface;

    /**
     * If container has a Schema for a given input.
     *
     * @param object $resourceObject
     * @return bool
     */
    public function hasSchema(object $resourceObject): bool;

    /**
     * Get schema provider by resource type.
     *
     * @param string $type
     * @return SchemaProviderInterface
     */
    public function getSchemaByType(string $type): SchemaProviderInterface;

    /**
     * Get schema provider by JSON:API type.
     *
     * @param string $resourceType
     * @return SchemaProviderInterface
     */
    public function getSchemaByResourceType(string $resourceType): SchemaProviderInterface;

    /**
     * Get a resource adapter for a domain record.
     *
     * @param object $record
     * @return ResourceAdapterInterface
     */
    public function getAdapter($record);

    /**
     * Get a resource adapter by domain record type.
     *
     * @param string $type
     * @return ResourceAdapterInterface
     */
    public function getAdapterByType($type);

    /**
     * Get a resource adapter by JSON API type.
     *
     * @param string $resourceType
     * @return ResourceAdapterInterface|null
     *      the resource type's adapter, or null if no adapter exists.
     */
    public function getAdapterByResourceType($resourceType);

    /**
     * Get a validator provider for a domain record.
     *
     * @param object $record
     * @return ValidatorFactoryInterface|null
     *      the validator provider, if there is one.
     */
    public function getValidators($record);

    /**
     * Get a validator provider by domain record type.
     *
     * @param string $type
     * @return ValidatorFactoryInterface|null
     *      the validator provider, if there is one.
     */
    public function getValidatorsByType($type);

    /**
     * Get a validator provider by JSON API type.
     *
     * @param $resourceType
     * @return ValidatorFactoryInterface|null
     *      the validator provider, if there is one.
     */
    public function getValidatorsByResourceType($resourceType);

    /**
     * Get a resource authorizer by domain record.
     *
     * @param $record
     * @return AuthorizerInterface|null
     *      the authorizer, if there is one.
     */
    public function getAuthorizer($record);

    /**
     * Get a resource authorizer by domain record type.
     *
     * @param $type
     * @return AuthorizerInterface|null
     *      the authorizer, if there is one.
     */
    public function getAuthorizerByType($type);

    /**
     * Get a resource authorizer by JSON API type.
     *
     * @param $resourceType
     * @return AuthorizerInterface|null
     *      the authorizer, if there is one.
     */
    public function getAuthorizerByResourceType($resourceType);

    /**
     * Get a multi-resource authorizer by name.
     *
     * @param string $name
     * @return AuthorizerInterface
     */
    public function getAuthorizerByName($name);

    /**
     * Get a content negotiator by JSON API resource type.
     *
     * @param $resourceType
     * @return ContentNegotiatorInterface|null
     *      the content negotiator, if there is one.
     */
    public function getContentNegotiatorByResourceType($resourceType);

    /**
     * Get a multi-resource content negotiator by name.
     *
     * @param $name
     * @return ContentNegotiatorInterface
     */
    public function getContentNegotiatorByName($name);

}
