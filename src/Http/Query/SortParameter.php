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

namespace CloudCreativity\LaravelJsonApi\Http\Query;

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\SortParameterInterface;
use InvalidArgumentException;

class SortParameter implements SortParameterInterface
{
    /**
     * @var string
     */
    private string $field;

    /**
     * @var bool
     */
    private bool $isAscending;

    /**
     * SortParameter constructor.
     *
     * @param string $field
     * @param bool $isAscending
     */
    public function __construct(string $field, bool $isAscending = true)
    {
        if (empty($field)) {
            throw new InvalidArgumentException('Expecting a non-empty sort field name.');
        }

        $this->field = $field;
        $this->isAscending = $isAscending;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * Fluent stringable method.
     *
     * @return string
     */
    public function toString(): string
    {
        $prefix = $this->isAscending() ? '' : '-';

        return $prefix . $this->getField();
    }

    /**
     * @inheritDoc
     */
    public function getField(): string
    {
        return $this->field;
    }

    /**
     * @inheritDoc
     */
    public function isAscending(): bool
    {
        return true === $this->isAscending;
    }

    /**
     * @inheritDoc
     */
    public function isDescending(): bool
    {
        return false === $this->isAscending;
    }
}