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

namespace CloudCreativity\JsonApi\Schema;

use InvalidArgumentException;
use Neomerx\JsonApi\Contracts\Parameters\ParametersInterface;
use Neomerx\JsonApi\Contracts\Parameters\SortParameterInterface;
use Neomerx\JsonApi\Contracts\Schema\LinkInterface;
use RuntimeException;

/**
 * Class Link
 * @package CloudCreativity\JsonApi
 */
class PaginationLink implements LinkInterface
{

    /**
     * @var string
     */
    private $subHref;

    /**
     * @var array|object|null
     */
    private $meta;

    /**
     * @var bool
     */
    private $treatAsHref;

    /**
     * @var array
     */
    private $sortParameters;

    /**
     * @var array
     */
    private $paginationParameters;

    /**
     * @var array
     */
    private $filteringParameters;

    /**
     * @param $subHref
     * @param ParametersInterface $parameters
     * @param null $meta
     * @param bool $treatAsHref
     * @return PaginationLink
     */
    public static function create(
        $subHref,
        ParametersInterface $parameters,
        $meta = null,
        $treatAsHref = false
    ) {
        return new self(
            $subHref,
            (array) $parameters->getPaginationParameters(),
            $parameters->getSortParameters(),
            $parameters->getFilteringParameters(),
            $meta,
            $treatAsHref
        );
    }

    /**
     * PaginationLink constructor.
     * @param $subHref
     * @param array $paginationParameters
     * @param null $sortParameters
     * @param null $filteringParameters
     * @param null $meta
     * @param bool $treatAsHref
     */
    public function __construct(
        $subHref,
        array $paginationParameters,
        $sortParameters = null,
        $filteringParameters = null,
        $meta = null,
        $treatAsHref = false
    ) {
        if (!is_string($subHref)) {
            throw new InvalidArgumentException('Expecting sub-href to be a string.');
        }

        $this->subHref = $subHref;
        $this->paginationParameters = (array) $paginationParameters;
        $this->sortParameters = (array) $sortParameters;
        $this->filteringParameters = (array) $filteringParameters;
        $this->meta = $meta;
        $this->treatAsHref = (bool) $treatAsHref;
    }

    /**
     * Get 'href' (URL) value.
     *
     * @return string
     */
    public function getSubHref()
    {
        $href = $this->subHref;
        $append = "";

        $stack = array_filter([
            $this->getPaginationParameters(),
            $this->getSortParameters(),
            $this->getFilteringParameters(),
        ]);

        foreach ($stack as $value) {

            if (!empty($append)) {
                $append .= '&';
            }

            $append .= $value;
        }

        return !empty($append) ? sprintf('%s?%s', $href, $append) : $href;
    }

    /**
     * Get meta information.
     *
     * @return array|object|null
     */
    public function getMeta()
    {
        return $this->meta;
    }

    /**
     * If $subHref is a full URL and must not be concatenated with other URLs.
     *
     * @return bool
     */
    public function isTreatAsHref()
    {
        return $this->treatAsHref;
    }

    /**
     * @return string
     */
    private function getPaginationParameters()
    {
        $params = "";

        foreach ($this->paginationParameters as $key => $value) {

            if (!empty($params)) {
                $params .= '&';
            }

            $params .= sprintf('page[%s]=%s', $key, $value);
        }

        return $params;
    }

    /**
     * @return string
     */
    private function getSortParameters()
    {
        $params = [];

        foreach ($this->sortParameters as $parameter) {

            if (!$parameter instanceof SortParameterInterface) {
                throw new RuntimeException('Expecting only sort parameters.');
            }

            $field = $parameter->getField();

            if ($parameter->isDescending()) {
                $field = '-' . $field;
            }

            $params[] = $field;
        }

        return !empty($params) ? 'sort=' . implode(',', $params) : '';
    }

    /**
     * @return string
     */
    private function getFilteringParameters()
    {
        $params = "";

        foreach ($this->filteringParameters as $key => $value) {

            if (!empty($params)) {
                $params .= '&';
            }

            $params .= sprintf('filter[%s]=%s', $key, $value);
        }

        return $params;
    }
}
