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

namespace CloudCreativity\LaravelJsonApi\Contracts\Schema;

use Neomerx\JsonApi\Contracts\Schema\ContextInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;

interface SchemaProviderInterface
{
    public const SHOW_SELF = 'showSelf';
    public const SHOW_RELATED = 'related';
    public const SHOW_DATA = 'showData';
    public const DATA = 'data';
    public const META = 'meta';
    public const LINKS = 'links';

    /**
     * Set the current context.
     *
     * @param ContextInterface|null $context
     * @return void
     */
    public function setContext(?ContextInterface $context): void;

    /**
     * Get the resource type.
     *
     * @return string
     */
    public function getResourceType(): string;

    /**
     * Get the resource id.
     *
     * @param object $resource
     * @return string
     */
    public function getId(object $resource): string;

    /**
     * Get the resource attributes.
     *
     * @param object $resource
     * @return array
     */
    public function getAttributes(object $resource): array;

    /**
     * Get the resource relationships.
     *
     * @param object $resource
     * @param bool $isPrimary
     * @param array $includedRelationships
     * @return array
     */
    public function getRelationships(object $resource, bool $isPrimary, array $includedRelationships): array;

    /**
     * Get the resource self url.
     *
     * @param object|null $resource
     * @return string
     */
    public function getSelfSubUrl(object $resource = null): string;

    /**
     * Get the resource self sub link.
     *
     * @param object $resource
     * @return LinkInterface
     */
    public function getSelfSubLink(object $resource): LinkInterface;

    /**
     * Get the relationship self link.
     *
     * @param object $resource
     * @param string $field
     * @return LinkInterface
     */
    public function getRelationshipSelfLink(object $resource, string $field): LinkInterface;

    /**
     * Get the relationship related link.
     *
     * @param object $resource
     * @param string $field
     * @return LinkInterface
     */
    public function getRelationshipRelatedLink(object $resource, string $field): LinkInterface;

    /**
     * Get schema default include paths.
     *
     * @return string[]
     */
    public function getIncludePaths(): array;
}