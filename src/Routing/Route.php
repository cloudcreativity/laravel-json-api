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

namespace CloudCreativity\LaravelJsonApi\Routing;

use CloudCreativity\LaravelJsonApi\Codec\Codec;
use CloudCreativity\LaravelJsonApi\Contracts\Queue\AsynchronousProcess;
use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\ResourceNotFoundException;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
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
     * @var Codec|null
     */
    private $codec;

    /**
     * Route constructor.
     *
     * @param ResolverInterface $resolver
     * @param IlluminateRoute|null $route
     *      the route, if one was successfully matched.
     */
    public function __construct(ResolverInterface $resolver, ?IlluminateRoute $route)
    {
        $this->resolver = $resolver;
        $this->route = $route;
    }

    /**
     * Substitute the route bindings onto the Laravel route.
     *
     * @param StoreInterface $store
     * @return void
     * @throws ResourceNotFoundException
     */
    public function substituteBindings(StoreInterface $store): void
    {
        /** Cache the ID values so that we still have access to them. */
        $tempResourceId = $this->getResourceId();
        $tempProcessId = $this->getProcessId();
        $this->resourceId = isset($tempResourceId) ? $tempResourceId : false;
        $this->processId = isset($tempProcessId) ? $tempProcessId : false;

        /** Bind the domain record. */
        if (!empty($this->resourceId) || '0' === $this->resourceId) {
            $this->route->setParameter(
                ResourceRegistrar::PARAM_RESOURCE_ID,
                $store->findOrFail($this->getResourceType(), $this->resourceId)
            );
        }

        /** Bind the async process. */
        if (!empty($this->processId) || '0' === $this->processId) {
            $this->route->setParameter(
                ResourceRegistrar::PARAM_PROCESS_ID,
                $store->findOrFail($this->getProcessType(), $this->processId)
            );
        }
    }

    /**
     * Set the matched codec.
     *
     * @param Codec $codec
     * @return $this
     */
    public function setCodec(Codec $codec): self
    {
        $this->codec = $codec;

        return $this;
    }

    /**
     * Get the matched codec.
     *
     * @return Codec
     */
    public function getCodec(): Codec
    {
        if (!$this->hasCodec()) {
            throw new RuntimeException('Codec cannot be obtained before content negotiation.');
        }

        return $this->codec;
    }

    /**
     * @return bool
     */
    public function hasCodec(): bool
    {
        return !!$this->codec;
    }

    /**
     * Get the domain record type for the route.
     *
     * For routes that support polymorphic types, the first PHP type that is
     * registered will be returned.
     *
     * @return string
     * @deprecated 2.0 use `getTypes()` as some routes may support polymorphic types.
     */
    public function getType(): string
    {
        $type = $this->getTypes()[0] ?? null;

        if (!$type) {
            throw new RuntimeException('Expecting at least one PHP type.');
        }

        return $type;
    }

    /**
     * Get the domain record types for the route.
     *
     * As some routes support polymorphic types, this method returns an array of PHP types.
     *
     * @return string[]
     */
    public function getTypes(): array
    {
        /** If we have resolved a specific record for the route, we know the exact class. */
        if ($resource = $this->getResource()) {
            return [get_class($resource)];
        }

        $resourceType = $this->getResourceType();

        if (!$type = $this->resolver->getType($resourceType)) {
            throw new RuntimeException("JSON API resource type {$resourceType} is not registered.");
        }

        return (array) $type;
    }

    /**
     * What is the resource type of the route?
     *
     * @return string|null
     *      the resource type
     */
    public function getResourceType(): ?string
    {
        return $this->parameter(ResourceRegistrar::PARAM_RESOURCE_TYPE);
    }

    /**
     * What is the resource id of the route?
     *
     * @return string|null
     */
    public function getResourceId(): ?string
    {
        if (is_null($this->resourceId)) {
            return $this->parameter(ResourceRegistrar::PARAM_RESOURCE_ID);
        }

        return $this->resourceId ?: null;
    }

    /**
     * Get the domain object binding for the route.
     *
     * @return mixed|null
     */
    public function getResource()
    {
        $resource = $this->parameter(ResourceRegistrar::PARAM_RESOURCE_ID);

        return is_object($resource) ? $resource : null;
    }

    /**
     * Get the relationship name for the route.
     *
     * @return string|null
     */
    public function getRelationshipName(): ?string
    {
        return $this->parameter(ResourceRegistrar::PARAM_RELATIONSHIP_NAME);
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
        return $this->parameter(ResourceRegistrar::PARAM_RELATIONSHIP_INVERSE_TYPE);
    }

    /**
     * Get the process resource type for the route.
     *
     * @return string|null
     */
    public function getProcessType(): ?string
    {
        return $this->parameter(ResourceRegistrar::PARAM_PROCESS_TYPE);
    }

    /**
     * Get the process id for the route.
     *
     * @return string|null
     */
    public function getProcessId(): ?string
    {
        if (is_null($this->processId)) {
            return $this->parameter(ResourceRegistrar::PARAM_PROCESS_ID);
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
        $process = $this->parameter(ResourceRegistrar::PARAM_PROCESS_ID);

        return ($process instanceof AsynchronousProcess) ? $process : null;
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

    /**
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    private function parameter(string $name, $default = null)
    {
        return $this->route ? $this->route->parameter($name, $default) : null;
    }

}
