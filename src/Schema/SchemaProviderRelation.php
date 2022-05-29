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
use Neomerx\JsonApi\Contracts\Schema\SchemaInterface;
use RuntimeException;

class SchemaProviderRelation
{
    /**
     * @var string
     */
    private string $resourceType;

    /**
     * @var string
     */
    private string $field;

    /**
     * @var array
     */
    private array $relation;

    /**
     * Fluent constructor.
     *
     * @param string $resourceType
     * @param string $field
     * @param array $relation
     * @return static
     */
    public static function make(string $resourceType, string $field, array $relation): self
    {
        return new self($resourceType, $field, $relation);
    }

    /**
     * SchemaProviderRelation constructor.
     *
     * @param string $resourceType
     * @param string $field
     * @param array $relation
     */
    public function __construct(string $resourceType, string $field, array $relation)
    {
        $this->resourceType = $resourceType;
        $this->field = $field;
        $this->relation = $relation;
    }

    /**
     * Should the data member be shown?
     *
     * @return bool
     */
    public function showData(): bool
    {
        if (!isset($this->relation[SchemaProviderInterface::SHOW_DATA])) {
            return array_key_exists(SchemaProviderInterface::DATA, $this->relation);
        }

        $value = $this->relation[SchemaProviderInterface::SHOW_DATA];

        if (is_bool($value)) {
            return $value;
        }

        throw new RuntimeException(sprintf(
            'Show data on resource "%s" relation "%s" must be a boolean.',
            $this->resourceType,
            $this->field,
        ));
    }

    /**
     * Get the data member.
     *
     * @return mixed
     */
    public function data()
    {
        return $this->relation[SchemaProviderInterface::DATA] ?? null;
    }

    /**
     * Should the self link be shown?
     *
     * @return bool|null
     */
    public function showSelfLink(): ?bool
    {
        $value = $this->relation[SchemaProviderInterface::SHOW_SELF] ?? null;

        if (null === $value || is_bool($value)) {
            return $value;
        }

        throw new RuntimeException(sprintf(
            'Show self link on resource "%s" relation "%s" must be a boolean.',
            $this->resourceType,
            $this->field,
        ));
    }

    /**
     * Should the related link be shown?
     *
     * @return bool|null
     */
    public function showRelatedLink(): ?bool
    {
        $value = $this->relation[SchemaProviderInterface::SHOW_RELATED] ?? null;

        if (null === $value || is_bool($value)) {
            return $value;
        }

        throw new RuntimeException(sprintf(
            'Show related link on resource "%s" relation "%s" must be a boolean.',
            $this->resourceType,
            $this->field,
        ));
    }

    /**
     * Does the relationship have meta?
     *
     * @return bool
     */
    public function hasMeta(): bool
    {
        $value = $this->meta();

        return !empty($value);
    }

    /**
     * Get the relationship meta.
     *
     * @return mixed
     */
    public function meta()
    {
        return $this->relation[SchemaProviderInterface::META] ?? null;
    }

    /**
     * Parse the legacy neomerx relation to a new one.
     *
     * @return array
     */
    public function parse(): array
    {
        $values = [];
        $showSelfLink = $this->showSelfLink();
        $showRelatedLink = $this->showRelatedLink();

        if ($this->showData()) {
            $values[SchemaInterface::RELATIONSHIP_DATA] = $this->data();
        }

        if (is_bool($showSelfLink)) {
            $values[SchemaInterface::RELATIONSHIP_LINKS_SELF] = $showSelfLink;
        }

        if (is_bool($showRelatedLink)) {
            $values[SchemaInterface::RELATIONSHIP_LINKS_RELATED] = $showRelatedLink;
        }

        if ($this->hasMeta()) {
            $values[SchemaInterface::RELATIONSHIP_META] = $this->meta();
        }

        return $values;
    }
}