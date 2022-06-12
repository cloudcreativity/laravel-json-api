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

use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\SortParameterInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Http\Query\BaseQueryParserInterface;

/**
 * Class QueryParameters
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class QueryParameters implements QueryParametersInterface, Arrayable
{
    /**
     * @var array|null
     */
    private ?array $includePaths;

    /**
     * @var array|null
     */
    private ?array $fieldSets;

    /**
     * @var SortParameterInterface[]|null
     */
    private ?array $sortParameters;

    /**
     * @var array|null
     */
    private ?array $pagingParameters;

    /**
     * @var array|null
     */
    private ?array $filteringParameters;

    /**
     * @var array|null
     */
    private ?array $unrecognizedParams;

    /**
     * @param QueryParametersInterface $parameters
     * @return QueryParameters
     */
    public static function cast(QueryParametersInterface $parameters)
    {
        if ($parameters instanceof self) {
            return $parameters;
        }

        return new self(
            $parameters->getIncludePaths(),
            $parameters->getFieldSets(),
            $parameters->getSortParameters(),
            $parameters->getPaginationParameters(),
            $parameters->getFilteringParameters(),
            $parameters->getUnrecognizedParameters()
        );
    }

    /**
     * QueryParameters constructor.
     *
     * @param string[]|null $includePaths
     * @param array|null $fieldSets
     * @param SortParameterInterface[]|null $sortParameters
     * @param array|null $pagingParameters
     * @param array|null $filteringParameters
     * @param array|null $unrecognizedParams
     */
    public function __construct(
        array $includePaths = null,
        array $fieldSets = null,
        array $sortParameters = null,
        array $pagingParameters = null,
        array $filteringParameters = null,
        array $unrecognizedParams = null
    ) {
        $this->fieldSets = $fieldSets;
        $this->includePaths = $includePaths;
        $this->sortParameters = $this->assertSortParameters($sortParameters);
        $this->pagingParameters = $pagingParameters;
        $this->unrecognizedParams = $unrecognizedParams;
        $this->filteringParameters = $filteringParameters;
    }

    /**
     * @inheritDoc
     */
    public function getIncludePaths(): ?array
    {
        return $this->includePaths;
    }

    /**
     * @inheritDoc
     */
    public function getFieldSets(): ?array
    {
        return $this->fieldSets;
    }

    /**
     * @inheritDoc
     */
    public function getFieldSet(string $type): ?array
    {
        $fieldSets = $this->fieldSets ?? [];

        return $fieldSets[$type] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function getSortParameters(): ?array
    {
        return $this->sortParameters;
    }

    /**
     * @inheritDoc
     */
    public function getPaginationParameters(): ?array
    {
        return $this->pagingParameters;
    }

    /**
     * @inheritDoc
     */
    public function getFilteringParameters(): ?array
    {
        return $this->filteringParameters;
    }

    /**
     * @inheritDoc
     */
    public function getUnrecognizedParameters(): ?array
    {
        return $this->unrecognizedParams;
    }

    /**
     * @inheritDoc
     */
    public function isEmpty(): bool
    {
        return
            empty($this->getFieldSets()) === true &&
            empty($this->getIncludePaths()) === true &&
            empty($this->getSortParameters()) === true &&
            empty($this->getPaginationParameters()) === true &&
            empty($this->getFilteringParameters()) === true;
    }

    /**
     * @return string|null
     */
    public function getIncludeParameter(): ?string
    {
        return implode(',', (array) $this->getIncludePaths()) ?: null;
    }

    /**
     * @return array
     */
    public function getFieldsParameter(): array
    {
        return Collection::make((array) $this->getFieldSets())->map(function ($values) {
            return implode(',', (array) $values);
        })->all();
    }

    /**
     * @return string|null
     */
    public function getSortParameter(): ?string
    {
        return implode(',', (array) $this->getSortParameters()) ?: null;
    }

    /**
     * @return array
     */
    public function all(): array
    {
        return array_replace($this->getUnrecognizedParameters() ?: [], [
            BaseQueryParserInterface::PARAM_INCLUDE =>
                $this->getIncludeParameter(),
            BaseQueryParserInterface::PARAM_FIELDS =>
                $this->getFieldsParameter() ?: null,
            BaseQueryParserInterface::PARAM_SORT =>
                $this->getSortParameter(),
            BaseQueryParserInterface::PARAM_PAGE =>
                $this->getPaginationParameters(),
            BaseQueryParserInterface::PARAM_FILTER =>
                $this->getFilteringParameters()
        ]);
    }

    /**
     * @inheritDoc
     */
    public function toArray(): array
    {
        return array_filter($this->all());
    }

    /**
     * @param array|null $sortParameters
     * @return array|null
     */
    private function assertSortParameters(?array $sortParameters): ?array
    {
        if (null === $sortParameters) {
            return null;
        }

        foreach ($sortParameters as $sortParameter) {
            if (!$sortParameter instanceof SortParameterInterface) {
                throw new \InvalidArgumentException('Expecting only sort parameter objects for the sort field.');
            }
        }

        return $sortParameters;
    }
}
