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

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;

/**
 * Class SchemaFields
 *
 * @see https://github.com/neomerx/json-api/issues/236
 * @see https://github.com/neomerx/json-api/issues/236#issuecomment-483978443
 */
class SchemaFields
{
    /**
     * @var string
     */
    private const PATH_SEPARATOR = '.';

    /**
     * @var string
     */
    private const FIELD_SEPARATOR = ',';

    /**
     * @var array
     */
    private array $fastRelationships = [];

    /**
     * @var array
     */
    private array $fastRelationshipLists = [];

    /**
     * @var array
     */
    private array $fastFields = [];

    /**
     * @var array
     */
    private array $fastFieldLists = [];

    /**
     * Make a new schema fields from encoding parameters.
     *
     * @param QueryParametersInterface|null $parameters
     * @return static
     */
    public static function make(?QueryParametersInterface $parameters): self
    {
        if ($parameters) {
            return new self(
                $parameters->getIncludePaths(),
                $parameters->getFieldSets(),
            );
        }

        return new self();
    }

    /**
     * SchemaFields constructor.
     *
     * @param iterable|null $paths
     * @param iterable|null $fieldSets
     */
    public function __construct(iterable $paths = null, iterable $fieldSets = null)
    {
        if (null !== $paths) {
            foreach ($paths as $path) {
                $path = RelationshipPath::cast($path);
                foreach ($path as $key => $relationship) {
                    $curPath = (0 === $key) ? '' : $path->take($key)->toString();
                    $this->fastRelationships[$curPath][$relationship] = true;
                    $this->fastRelationshipLists[$curPath][$relationship] = $relationship;
                }
            }
        }

        if (null !== $fieldSets) {
            foreach ($fieldSets as $type => $fieldList) {
                $fieldList = \is_string($fieldList) ? \explode(static::FIELD_SEPARATOR, $fieldList) : $fieldList;
                foreach ($fieldList as $field) {
                    $this->fastFields[$type][$field] = true;
                    $this->fastFieldLists[$type][$field] = $field;
                }
            }
        }
    }

    /**
     * @param string $currentPath
     * @param string $relationship
     * @return bool
     */
    public function isRelationshipRequested(string $currentPath, string $relationship): bool
    {
        return isset($this->fastRelationships[$currentPath][$relationship]);
    }

    /**
     * @param string $currentPath
     * @return array
     */
    public function getRequestedRelationships(string $currentPath): array
    {
        return $this->fastRelationshipLists[$currentPath] ?? [];
    }

    /**
     * @param string $type
     * @param string $field
     * @return bool
     */
    public function isFieldRequested(string $type, string $field): bool
    {
        return \array_key_exists($type, $this->fastFields) === false ? true : isset($this->fastFields[$type][$field]);
    }

    /**
     * @param string $type
     * @return array|null
     */
    public function getRequestedFields(string $type): ?array
    {
        return $this->fastFieldLists[$type] ?? null;
    }
}
