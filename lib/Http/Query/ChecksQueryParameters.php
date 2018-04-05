<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Http\Query;

use CloudCreativity\JsonApi\Contracts\Factories\FactoryInterface;
use CloudCreativity\JsonApi\Contracts\Validators\QueryValidatorInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryCheckerInterface;

/**
 * Class ChecksQueryParameters
 *
 * @package CloudCreativity\JsonApi
 */
trait ChecksQueryParameters
{

    /**
     * Whether unrecognized parameters should be allowed.
     *
     * @return bool
     */
    protected function allowUnrecognizedParameters()
    {
        if (property_exists($this, 'allowUnrecognizedParameters')) {
            return (bool) $this->allowUnrecognizedParameters;
        }

        return false;
    }

    /**
     * What include paths the client is allowed to request.
     *
     * Empty array = clients are not allowed to specify include paths.
     * Null = all paths are allowed.
     *
     * @return string[]|null
     */
    protected function allowedIncludePaths()
    {
        if (property_exists($this, 'allowedIncludePaths')) {
            return $this->allowedIncludePaths;
        }

        return [];
    }

    /**
     * What field sets the client is allowed to request per JSON API resource object type.
     *
     * Null = the client can specify any fields for any resource object type.
     * Empty array = the client cannot specify any fields for any resource object type (i.e. all denied.)
     * Non-empty array = configuration per JSON API resource object type. The key should be the type, the value should
     * be either null (all fields allowed for that type), empty array (no fields allowed for that type) or an array
     * of string values listing the allowed fields for that type.
     *
     * @return array|null
     */
    protected function allowedFieldSetTypes()
    {
        if (property_exists($this, 'allowedFieldSetTypes')) {
            return $this->allowedFieldSetTypes;
        }

        return null;
    }

    /**
     * What sort field names can be sent by the client.
     *
     * Empty array = clients are not allowed to specify sort fields.
     * Null = clients can specify any sort fields.
     *
     * @return string[]|null
     */
    protected function allowedSortParameters()
    {
        if (property_exists($this, 'allowedSortParameters')) {
            return $this->allowedSortParameters;
        }

        return [];
    }

    /**
     * What paging fields can be sent by the client.
     *
     * Empty array = clients are not allowed to request paging.
     * Null = clients can specify any paging fields they want.
     *
     * @return string[]|null
     */
    protected function allowedPagingParameters()
    {
        if (property_exists($this, 'allowedPagingParameters')) {
            return $this->allowedPagingParameters;
        }

        return [];
    }

    /**
     * What filtering fields can be sent by the client.
     *
     * Empty array = clients are not allowed to request filtering.
     * Null = clients can specify any filtering fields they want.
     *
     * @return string[]|null
     */
    protected function allowedFilteringParameters()
    {
        if (property_exists($this, 'allowedFilteringParameters')) {
            return $this->allowedFilteringParameters;
        }

        return [];
    }

    /**
     * @param FactoryInterface $factory
     * @param QueryValidatorInterface|null $queryValidator
     * @return QueryCheckerInterface
     */
    protected function createQueryChecker(FactoryInterface $factory, QueryValidatorInterface $queryValidator = null)
    {
        return $factory->createExtendedQueryChecker(
            $this->allowUnrecognizedParameters(),
            $this->allowedIncludePaths(),
            $this->allowedFieldSetTypes(),
            $this->allowedSortParameters(),
            $this->allowedPagingParameters(),
            $this->allowedFilteringParameters(),
            $queryValidator
        );
    }

}
