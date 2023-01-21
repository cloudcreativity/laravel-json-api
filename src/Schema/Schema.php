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
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use Neomerx\JsonApi\Schema\BaseSchema;

class Schema extends BaseSchema
{
    /**
     * @var SchemaProviderInterface
     */
    private SchemaProviderInterface $provider;

    /**
     * @var SchemaFields
     */
    private SchemaFields $fields;

    /**
     * Schema constructor.
     *
     * @param FactoryInterface $factory
     * @param SchemaProviderInterface $provider
     * @param SchemaFields|null $fields
     */
    public function __construct(
        FactoryInterface $factory,
        SchemaProviderInterface $provider,
        SchemaFields $fields = null
    ) {
        parent::__construct($factory);
        $this->provider = $provider;
        $this->fields = $fields ?? new SchemaFields();
    }

    /**
     * @inheritDoc
     */
    public function getType(): string
    {
        return $this->provider->getResourceType();
    }

    /**
     * @inheritDoc
     */
    public function getId($resource): ?string
    {
        return $this->provider->getId($resource);
    }

    /**
     * @inheritDoc
     */
    public function getAttributes($resource, ContextInterface $context): iterable
    {
        $this->provider->setContext($context);
        $attributes = $this->provider->getAttributes($resource);
        $this->provider->setContext(null);

        return $attributes;
    }

    /**
     * @inheritDoc
     */
    public function getRelationships($resource, ContextInterface $context): iterable
    {
        $isPrimary = (0 === $context->getPosition()->getLevel());
        $includeRelationships = $this->fields->getRequestedRelationships(
            $context->getPosition()->getPath()
        );

        $this->provider->setContext($context);
        $relations = $this->provider->getRelationships($resource, $isPrimary, $includeRelationships);
        $this->provider->setContext(null);
        $resourceType = $this->getType();

        foreach ($relations as $field => $relation) {
            yield $field => SchemaProviderRelation::make($resourceType, $field, $relation)->parse();
        }
    }

    /**
     * @inheritDoc
     */
    protected function getResourcesSubUrl(): string
    {
        return $this->provider->getSelfSubUrl();
    }

    /**
     * @inheritDoc
     */
    protected function getSelfSubUrl($resource): string
    {
        return $this->provider->getSelfSubUrl($resource);
    }

    /**
     * @inheritDoc
     */
    public function getRelationshipSelfLink($resource, string $name): LinkInterface
    {
        return $this->provider->getRelationshipSelfLink($resource, $name);
    }

    /**
     * @inheritDoc
     */
    public function getRelationshipRelatedLink($resource, string $name): LinkInterface
    {
        return $this->provider->getRelationshipRelatedLink($resource, $name);
    }
}