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

namespace CloudCreativity\LaravelJsonApi\Resolver;

use CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use IteratorAggregate;

class AggregateResolver implements ResolverInterface, IteratorAggregate
{

    /**
     * The API resolver.
     *
     * @var ResolverInterface
     */
    private $api;

    /**
     * The package resolvers.
     *
     * @var ResolverInterface[]
     */
    private $packages;

    /**
     * AggregateResolver constructor.
     *
     * @param ResolverInterface $api
     * @param ResolverInterface ...$packages
     */
    public function __construct(ResolverInterface $api, ResolverInterface ...$packages)
    {
        $this->api = $api;
        $this->packages = $packages;
    }

    /**
     * Attach a package resolver.
     *
     * @param ResolverInterface $resolver
     */
    public function attach(ResolverInterface $resolver)
    {
        if ($this === $resolver) {
            throw new RuntimeException('Cannot attach a resolver to itself.');
        }

        $this->packages[] = $resolver;
    }

    /**
     * @return ResolverInterface
     */
    public function getDefaultResolver()
    {
        return $this->api;
    }

    /**
     * @inheritDoc
     */
    public function getIterator(): \Generator
    {
        yield $this->api;

        foreach ($this->packages as $package) {
            yield $package;
        }
    }

    /**
     * @inheritDoc
     */
    public function isType($type)
    {
        return !is_null($this->resolverByType($type));
    }

    /**
     * @inheritDoc
     */
    public function getType($resourceType)
    {
        $resolver = $this->resolverByResourceType($resourceType);

        return $resolver ? $resolver->getType($resourceType) : null;
    }

    /**
     * @inheritDoc
     */
    public function getAllTypes()
    {
        $all = [];

        foreach ($this->packages as $resolver) {
            $all = array_merge($all, $resolver->getAllTypes());
        }

        return $all;
    }

    /**
     * @inheritDoc
     */
    public function isResourceType($resourceType)
    {
        return !is_null($this->resolverByResourceType($resourceType));
    }

    /**
     * @inheritDoc
     */
    public function getResourceType($type)
    {
        $resolver = $this->resolverByType($type);

        return $resolver ? $resolver->getResourceType($type) : null;
    }

    /**
     * @inheritDoc
     */
    public function getAllResourceTypes()
    {
        $all = [];

        foreach ($this->packages as $resolver) {
            $all = array_merge($all, $resolver->getAllResourceTypes());
        }

        return $all;
    }

    /**
     * @inheritDoc
     */
    public function getSchemaByType($type)
    {
        $resolver = $this->resolverByType($type);

        return $resolver ? $resolver->getSchemaByType($type) : null;
    }

    /**
     * @inheritDoc
     */
    public function getSchemaByResourceType($resourceType)
    {
        $resolver = $this->resolverByResourceType($resourceType) ?: $this->api;

        return $resolver->getSchemaByResourceType($resourceType);
    }

    /**
     * @inheritDoc
     */
    public function getAdapterByType($type)
    {
        $resolver = $this->resolverByType($type);

        return $resolver ? $resolver->getAdapterByType($type) : null;
    }

    /**
     * @inheritDoc
     */
    public function getAdapterByResourceType($resourceType)
    {
        $resolver = $this->resolverByResourceType($resourceType) ?: $this->api;

        return $resolver->getAdapterByResourceType($resourceType);
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizerByType($type)
    {
        $resolver = $this->resolverByType($type);

        return $resolver ? $resolver->getAuthorizerByType($type) : null;
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizerByResourceType($resourceType)
    {
        $resolver = $this->resolverByResourceType($resourceType) ?: $this->api;

        return $resolver->getAuthorizerByResourceType($resourceType);
    }

    /**
     * @inheritDoc
     */
    public function getAuthorizerByName($name)
    {
        return $this->getDefaultResolver()->getAuthorizerByName($name);
    }

    /**
     * @inheritDoc
     */
    public function getContentNegotiatorByResourceType($resourceType)
    {
        $resolver = $this->resolverByResourceType($resourceType);

        return $resolver ? $resolver->getContentNegotiatorByResourceType($resourceType) : null;
    }

    /**
     * @inheritDoc
     */
    public function getContentNegotiatorByName($name)
    {
        return $this->getDefaultResolver()->getContentNegotiatorByName($name);
    }

    /**
     * @inheritDoc
     */
    public function getValidatorsByType($type)
    {
        $resolver = $this->resolverByType($type);

        return $resolver ? $resolver->getValidatorsByType($type) : null;
    }

    /**
     * @inheritDoc
     */
    public function getValidatorsByResourceType($resourceType)
    {
        $resolver = $this->resolverByResourceType($resourceType) ?: $this->api;

        return $resolver->getValidatorsByResourceType($resourceType);
    }

    /**
     * @param $type
     * @return ResolverInterface|null
     */
    private function resolverByType($type)
    {
        /** @var ResolverInterface $resolver */
        foreach ($this as $resolver) {
            if ($resolver->isType($type)) {
                return $resolver;
            }
        }

        return null;
    }

    /**
     * @param $resourceType
     * @return ResolverInterface|null
     */
    private function resolverByResourceType($resourceType)
    {
        /** @var ResolverInterface $resolver */
        foreach ($this as $resolver) {
            if ($resolver->isResourceType($resourceType)) {
                return $resolver;
            }
        }

        return null;
    }
}
