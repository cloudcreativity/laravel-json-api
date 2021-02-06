<?php
/*
 * Copyright 2021 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Encoder\Parameters;

use Illuminate\Contracts\Support\Arrayable;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;
use Neomerx\JsonApi\Encoder\Parameters\EncodingParameters as NeomerxEncodingParameters;

/**
 * Class EncodingParameters
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class EncodingParameters extends NeomerxEncodingParameters implements Arrayable
{

    /**
     * @param EncodingParametersInterface $parameters
     * @return EncodingParameters
     */
    public static function cast(EncodingParametersInterface $parameters)
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
     * @return string|null
     */
    public function getIncludeParameter()
    {
        return implode(',', (array) $this->getIncludePaths()) ?: null;
    }

    /**
     * @return array
     */
    public function getFieldsParameter()
    {
        return collect((array) $this->getFieldSets())->map(function ($values) {
            return implode(',', (array) $values);
        })->all();
    }

    /**
     * @return string|null
     */
    public function getSortParameter()
    {
        return implode(',', (array) $this->getSortParameters()) ?: null;
    }

    /**
     * @return array
     */
    public function all()
    {
        return array_replace($this->getUnrecognizedParameters() ?: [], [
            QueryParametersParserInterface::PARAM_INCLUDE =>
                $this->getIncludeParameter(),
            QueryParametersParserInterface::PARAM_FIELDS =>
                $this->getFieldsParameter() ?: null,
            QueryParametersParserInterface::PARAM_SORT =>
                $this->getSortParameter(),
            QueryParametersParserInterface::PARAM_PAGE =>
                $this->getPaginationParameters(),
            QueryParametersParserInterface::PARAM_FILTER =>
                $this->getFilteringParameters()
        ]);
    }

    /**
     * @inheritDoc
     */
    public function toArray()
    {
        return array_filter($this->all());
    }

}
