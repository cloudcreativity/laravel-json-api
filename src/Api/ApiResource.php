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

use CloudCreativity\LaravelJsonApi\Utils\Fqn;

/**
 * Class ApiResource
 *
 * @package CloudCreativity\LaravelJsonApi
 * @todo make final as this is not intended to be extended.
 */
class ApiResource
{

    /**
     * @var string
     */
    private $resourceType;

    /**
     * @var string
     */
    private $recordFqn;

    /**
     * @var string
     */
    private $rootNamespace;

    /**
     * @var bool
     */
    private $byResource;

    /**
     * ApiResource constructor.
     *
     * @param string $resourceType
     * @param string $recordFqn
     * @param string $rootNamespace
     * @param bool $byResource
     */
    public function __construct($resourceType, $recordFqn, $rootNamespace, $byResource = true)
    {
        $this->resourceType = $resourceType;
        $this->recordFqn = $recordFqn;
        $this->rootNamespace = $rootNamespace;
        $this->byResource = $byResource;
    }

    /**
     * Get the JSON API resource type.
     *
     * @return string
     */
    public function getResourceType()
    {
        return $this->resourceType;
    }

    /**
     * Get the fully-qualified class name of the record (model/entity) the resource represents.
     *
     * @return string
     */
    public function getRecordFqn()
    {
        return $this->recordFqn;
    }

    /**
     * Get the fully-qualified class name of the resource's schema.
     *
     * There must always be a schema, so we do not need to check that the class exists.
     *
     * @return string
     */
    public function getSchemaFqn()
    {
        return Fqn::schema($this->resourceType, $this->rootNamespace, $this->byResource);
    }

    /**
     * Get the fully-qualified class name of the resource's adapter.
     *
     * There must always be an adapter, so we do not need to check that the class exists.
     *
     * @return string
     */
    public function getAdapterFqn()
    {
        return Fqn::adapter($this->resourceType, $this->rootNamespace, $this->byResource);
    }

    /**
     * Get the fully-qualified class name of the resource's authorizer.
     *
     * A resource-specific adapter does not need to exist, so we only return the class if
     * it exists.
     *
     * @return string|null
     */
    public function getAuthorizerFqn()
    {
        $fqn = Fqn::authorizer($this->resourceType, $this->rootNamespace, $this->byResource);

        return class_exists($fqn) ? $fqn : null;
    }

    /**
     * Get the fully-qualified class name of the resource's validators.
     *
     * A resource-specific set of validators does not need to exist, so we only return
     * the class if it exists.
     *
     * @return string|null
     */
    public function getValidatorsFqn()
    {
        $fqn = Fqn::validators($this->resourceType, $this->rootNamespace, $this->byResource);

        return class_exists($fqn) ? $fqn : null;
    }
}
