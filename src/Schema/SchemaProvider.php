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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Schema;

use CloudCreativity\LaravelJsonApi\Contracts\Schema\SchemaProviderInterface;
use Illuminate\Database\Eloquent\Model;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use Neomerx\JsonApi\Contracts\Schema\DocumentInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use RuntimeException;

abstract class SchemaProvider implements SchemaProviderInterface
{
    /**
     * @var string|null
     */
    protected string $resourceType = '';

    /**
     * @var string
     */
    protected string $selfSubUrl = '';

    /**
     * @var FactoryInterface
     */
    private FactoryInterface $factory;

    /**
     * @var ContextInterface|null
     */
    private ?ContextInterface $context = null;

    /**
     * SchemaProvider constructor.
     *
     * @param FactoryInterface $factory
     */
    public function __construct(FactoryInterface $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @inheritDoc
     */
    public function setContext(?ContextInterface $context): void
    {
        $this->context = $context;
    }

    /**
     * @inheritDoc
     */
    public function getResourceType(): string
    {
        if (empty($this->resourceType)) {
            throw new RuntimeException(sprintf(
                'No resource type set on schema %s.',
                static::class,
            ));
        }

        return $this->resourceType;
    }

    /**
     * @inheritDoc
     */
    public function getId(object $resource): string
    {
        if ($resource instanceof Model) {
            return (string) $resource->getRouteKey();
        }

        throw new RuntimeException(sprintf(
            'Id method must be implemented on schema %s.',
            static::class,
        ));
    }

    /**
     * @inheritDoc
     */
    public function getAttributes(object $resource): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getRelationships(object $resource, bool $isPrimary, array $includedRelationships): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getSelfSubUrl(object $resource = null): string
    {
        if (empty($this->selfSubUrl)) {
            $this->selfSubUrl = '/' . $this->getResourceType();
        }

        if ($resource) {
            return $this->selfSubUrl . '/' . $this->getId($resource);
        }

        return $this->selfSubUrl;
    }

    /**
     * @inheritDoc
     */
    public function getSelfSubLink(object $resource): LinkInterface
    {
        return $this->factory->createLink(
            true,
            $this->getSelfSubUrl($resource),
            false,
        );
    }

    /**
     * @inheritDoc
     */
    public function getRelationshipSelfLink(object $resource, string $field): LinkInterface
    {
        $url = $this->getRelationshipSelfUrl($resource, $field);

        return $this->factory->createLink(
            true,
            $url,
            false,
        );
    }

    /**
     * @inheritDoc
     */
    public function getRelationshipRelatedLink(object $resource, string $field): LinkInterface
    {
        $url = $this->getRelationshipRelatedUrl($resource, $field);

        return $this->factory->createLink(
            true,
            $url,
            false,
        );
    }

    /**
     * @inheritDoc
     */
    public function getIncludePaths(): array
    {
        return [];
    }

    /**
     * Get the relationship self url.
     *
     * @param object $resource
     * @param string $field
     * @return string
     */
    protected function getRelationshipSelfUrl(object $resource, string $field): string
    {
        return $this->getSelfSubUrl($resource) . '/' . DocumentInterface::KEYWORD_RELATIONSHIPS . '/' . $field;
    }

    /**
     * Get the relationship related url.
     *
     * @param object $resource
     * @param string $field
     * @return string
     */
    protected function getRelationshipRelatedUrl(object $resource, string $field): string
    {
        return $this->getSelfSubUrl($resource) . '/' . $field;
    }

    /**
     * @return ContextInterface
     */
    protected function getContext(): ContextInterface
    {
        if ($this->context) {
            return $this->context;
        }

        throw new RuntimeException('No currenct context set.');
    }
}