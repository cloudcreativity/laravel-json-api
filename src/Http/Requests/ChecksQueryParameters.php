<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use CloudCreativity\JsonApi\Contracts\Validators\FilterValidatorInterface;
use CloudCreativity\JsonApi\Exceptions\ValidationException;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\HttpFactoryInterface;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * Class ChecksQueryParameters
 * @package CloudCreativity\LaravelJsonApi
 */
trait ChecksQueryParameters
{

    /**
     * Whether unrecognized parameters should be allowed.
     *
     * @return bool
     */
    abstract protected function allowUnrecognizedParameters();

    /**
     * What include paths the client is allowed to request.
     *
     * Empty array = clients are not allowed to specify include paths.
     * Null = all paths are allowed.
     *
     * @return string[]|null
     */
    abstract protected function allowedIncludePaths();

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
    abstract protected function allowedFieldSetTypes();

    /**
     * What sort field names can be sent by the client.
     *
     * Empty array = clients are not allowed to specify sort fields.
     * Null = clients can specify any sort fields.
     *
     * @return string[]|null
     */
    abstract protected function allowedSortParameters();

    /**
     * What paging fields can be sent by the client.
     *
     * Empty array = clients are not allowed to request paging.
     * Null = clients can specify any paging fields they want.
     *
     * @return string[]|null
     */
    abstract protected function allowedPagingParameters();

    /**
     * What filtering fields can be sent by the client.
     *
     * Empty array = clients are not allowed to request filtering.
     * Null = clients can specify any filtering fields they want.
     *
     * @return string[]|null
     */
    abstract protected function allowedFilteringParameters();

    /**
     * @param HttpFactoryInterface $factory
     * @param JsonApiRequest $request
     * @param FilterValidatorInterface|null $filterValidator
     */
    protected function checkQueryParameters(
        HttpFactoryInterface $factory,
        JsonApiRequest $request,
        FilterValidatorInterface $filterValidator = null
    ) {
        $parameters = $request->getParameters();
        $this->checkEncodingParameters($factory, $parameters);

        if ($filterValidator) {
            $this->checkFilterParameters($filterValidator, $parameters);
        }
    }

    /**
     * @param HttpFactoryInterface $factory
     * @param EncodingParametersInterface $parameters
     * @throws JsonApiException
     */
    private function checkEncodingParameters(HttpFactoryInterface $factory, EncodingParametersInterface $parameters)
    {
        $checker = $factory->createQueryChecker(
            $this->allowUnrecognizedParameters(),
            $this->allowedIncludePaths(),
            $this->allowedFieldSetTypes(),
            $this->allowedSortParameters(),
            $this->allowedPagingParameters(),
            $this->allowedFilteringParameters()
        );

        $checker->checkQuery($parameters);
    }

    /**
     * @param FilterValidatorInterface $validator
     * @param EncodingParametersInterface $parameters
     * @throws JsonApiException
     */
    private function checkFilterParameters(FilterValidatorInterface $validator, EncodingParametersInterface $parameters)
    {
        if (!$validator->isValid((array) $parameters->getFilteringParameters())) {
            throw new ValidationException($validator->getErrors());
        }
    }

}
