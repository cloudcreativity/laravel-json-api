<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Routing;

use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Object\ResourceIdentifier;
use Illuminate\Routing\Route as IlluminateRoute;

/**
 * Class Route
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class Route
{

    /**
     * @var IlluminateRoute
     */
    private $route;

    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * @var string|null
     */
    private $resourceId;

    /**
     * @var string|null
     */
    private $processId;

    /**
     * Route constructor.
     *
     * @param IlluminateRoute $route
     * @param ResolverInterface $resolver
     */
    public function __construct(IlluminateRoute $route, ResolverInterface $resolver)
    {
        $this->route = $route;
        $this->resolver = $resolver;
    }

    /**
     * Get the domain record type for the route.
     *
     * @return string
     */
    public function getType(): string
    {
        if ($resource = $this->getResource()) {
            return get_class($resource);
        }

        $resourceType = $this->getResourceType();

        if (!$type = $this->resolver->getType($resourceType)) {
            throw new RuntimeException("JSON API resource type {$resourceType} is not registered.");
        }

        return $type;
    }

    /**
     * What is the resource type of the route?
     *
     * @return string|null
     *      the resource type
     */
    public function getResourceType(): ?string
    {
        return $this->route->parameter(ResourceRegistrar::PARAM_RESOURCE_TYPE);
    }

    /**
     * What is the resource id of the route?
     *
     * @return string|null
     */
    public function getResourceId(): ?string
    {
        /** Cache the resource id because binding substitutions will override it. */
        if (is_null($this->resourceId)) {
            $this->resourceId = $this->route->parameter(ResourceRegistrar::PARAM_RESOURCE_ID) ?: false;
        }

        return $this->resourceId ?: null;
    }

    /**
     * Get the resource identifier for the route.
     *
     * @return ResourceIdentifierInterface|null
     * @deprecated 2.0.0
     */
    public function getResourceIdentifier(): ?ResourceIdentifierInterface
    {
        if (!$resourceId = $this->getResourceId()) {
            return null;
        }

        return ResourceIdentifier::create($this->getResourceType(), $resourceId);
    }

    /**
     * Get the domain object binding for the route.
     *
     * @return mixed|null
     */
    public function getResource()
    {
        $resource = $this->route->parameter(ResourceRegistrar::PARAM_RESOURCE_ID);

        return is_object($resource) ? $resource : null;
    }

    /**
     * Get the relationship name for the route.
     *
     * @return string|null
     */
    public function getRelationshipName(): ?string
    {
        return $this->route->parameter(ResourceRegistrar::PARAM_RELATIONSHIP_NAME);
    }

    /**
     * Get the the inverse resource type for the route.
     *
     * For example, a `GET /posts/1/author`, the string returned by this method
     * would be `users` if the related author is a `users` JSON API resource type.
     *
     * @return string|null
     */
    public function getInverseResourceType(): ?string
    {
        return $this->route->parameter(ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE);
    }

    /**
     * Get the process resource type for the route.
     *
     * @return string|null
     */
    public function getProcessType(): ?string
    {
        return $this->route->parameter(ResourceRegistrar::PARAM_PROCESS_TYPE);
    }

    /**
     * Get the process id for the route.
     *
     * @return string|null
     */
    public function getProcessId(): ?string
    {
        /** Cache the process id because binding substitutions will override it. */
        if (is_null($this->processId)) {
            $this->processId = $this->route->parameter(ResourceRegistrar::PARAM_PROCESS_ID) ?: false;
        }

        return $this->processId ?: null;
    }

    /**
     * Get the process binding for the route.
     *
     * @return AsynchronousProcess|null
     */
    public function getProcess(): ?AsynchronousProcess
    {
        $process = $this->route->parameter(ResourceRegistrar::PARAM_PROCESS_ID);

        return ($process instanceof AsynchronousProcess) ? $process : null;
    }

    /**
     * Get the process identifier for the route.
     *
     * @return ResourceIdentifierInterface|null
     * @deprecated 2.0.0
     */
    public function getProcessIdentifier(): ?ResourceIdentifierInterface
    {
        if (!$id = $this->getProcessId()) {
            return null;
        }

        return ResourceIdentifier::create($this->getProcessType(), $id);
    }

    /**
     * @return bool
     */
    public function isResource(): bool
    {
        return !empty($this->getResourceId());
    }

    /**
     * @return bool
     */
    public function isNotResource(): bool
    {
        return !$this->isResource();
    }

    /**
     * @return bool
     */
    public function isRelationship(): bool
    {
        return !empty($this->getRelationshipName());
    }

    /**
     * @return bool
     */
    public function isNotRelationship(): bool
    {
        return !$this->isRelationship();
    }

    /**
     * @return bool
     */
    public function isProcesses(): bool
    {
        return !empty($this->getProcessType());
    }

    /**
     * @return bool
     */
    public function isNotProcesses(): bool
    {
        return !$this->isProcesses();
    }

    /**
     * @return bool
     */
    public function isProcess(): bool
    {
        return !empty($this->getProcessId());
    }

    /**
     * @return bool
     */
    public function isNotProcess(): bool
    {
        return !$this->isProcess();
    }

}
