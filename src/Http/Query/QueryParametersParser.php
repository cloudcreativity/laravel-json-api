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
use CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersParserInterface;
use CloudCreativity\LaravelJsonApi\Http\Query\QueryParameters;
use CloudCreativity\LaravelJsonApi\Http\Query\SortParameter;
use Neomerx\JsonApi\Http\Query\BaseQueryParser;
use Neomerx\JsonApi\Http\Query\BaseQueryParserTrait;
use RuntimeException;
use Traversable;

class QueryParametersParser implements QueryParametersParserInterface
{
    use BaseQueryParserTrait;

    /**
     * @inheritDoc
     */
    public function parseQueryParameters(array $parameters): QueryParametersInterface
    {
        $message = BaseQueryParser::MSG_ERR_INVALID_PARAMETER;

        return new QueryParameters(
            $this->getIncludeParameters($parameters, $message),
            $this->getFieldParameters($parameters, $message),
            $this->getSortParameters($parameters, $message),
            $parameters[BaseQueryParser::PARAM_PAGE] ?? null,
            $parameters[BaseQueryParser::PARAM_FILTER] ?? null,
            $this->getUnrecognizedParameters($parameters),
        );
    }

    /**
     * Parse include parameters.
     *
     * @param array $parameters
     * @param string $message
     * @return array|null
     */
    private function getIncludeParameters(array $parameters, string $message): ?array
    {
        if (!array_key_exists(BaseQueryParser::PARAM_INCLUDE, $parameters)) {
            return null;
        }

        // convert null to empty array, as the client has specified no include parameters.
        if (null === $parameters[BaseQueryParser::PARAM_INCLUDE]) {
            return [];
        }

        return $this->iteratorToArray($this->getIncludePaths($parameters, $message));
    }

    /**
     * Parse sparse field sets
     *
     * @param array $parameters
     * @param string $message
     * @return array|null
     */
    private function getFieldParameters(array $parameters, string $message): ?array
    {
        if (!array_key_exists(BaseQueryParser::PARAM_FIELDS, $parameters)) {
            return null;
        }

        // convert null to empty array, as the client has specified no sparse fields
        if (null === $parameters[BaseQueryParser::PARAM_FIELDS]) {
            return [];
        }

        $fieldSets = [];

        foreach ($this->getFields($parameters, $message) as $type => $fieldList) {
            $fieldSets[$type] = $this->iteratorToArray($fieldList);
        }

        return $fieldSets;
    }

    /**
     * Parse sort parameters.
     *
     * @param array $parameters
     * @param string $message
     * @return SortParameter[]|null
     */
    private function getSortParameters(array $parameters, string $message): ?array
    {
        if (!array_key_exists(BaseQueryParser::PARAM_SORT, $parameters)) {
            return null;
        }

        // convert null to empty array, as the client has specified no sort parameters.
        if (null === $parameters[BaseQueryParser::PARAM_SORT]) {
            return [];
        }

        $values = [];

        foreach ($this->getSorts($parameters, $message) as $field => $isAsc) {
            $values[] = new SortParameter($field, $isAsc);
        }

        return $values;
    }

    /**
     * Parse unrecognized parameters.
     *
     * @param array $parameters
     * @return array|null
     */
    private function getUnrecognizedParameters(array $parameters): ?array
    {
        unset(
            $parameters[BaseQueryParser::PARAM_INCLUDE],
            $parameters[BaseQueryParser::PARAM_FIELDS],
            $parameters[BaseQueryParser::PARAM_SORT],
            $parameters[BaseQueryParser::PARAM_PAGE],
            $parameters[BaseQueryParser::PARAM_FILTER],
        );

        return empty($parameters) ? null : $parameters;
    }

    /**
     * @param iterable $value
     * @return array
     */
    private function iteratorToArray(iterable $value): array
    {
        if ($value instanceof Traversable) {
            return iterator_to_array($value);
        }

        if (is_array($value)) {
            return $value;
        }

        throw new RuntimeException('Unexpected iterable value.');
    }
}