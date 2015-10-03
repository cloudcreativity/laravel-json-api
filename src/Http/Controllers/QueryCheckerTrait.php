<?php

/**
 * Copyright 2015 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Http\Controllers;

use App;
use CloudCreativity\JsonApi\Contracts\Integration\EnvironmentInterface;
use Neomerx\JsonApi\Contracts\Integration\ExceptionThrowerInterface;
use Neomerx\JsonApi\Contracts\Parameters\ParametersFactoryInterface;
use Neomerx\JsonApi\Contracts\Parameters\ParametersInterface;
use Neomerx\JsonApi\Contracts\Parameters\QueryCheckerInterface;

/**
 * Class QueryCheckerTrait
 * @package CloudCreativity\JsonApi\Laravel
 */
trait QueryCheckerTrait
{

    /**
     * Whether unrecognized parameters should be allowed.
     *
     * @var bool
     */
    protected $allowUnrecognizedParams = false;

    /**
     * What include paths the client is allowed to request.
     *
     * Empty array = clients are not allowed to specify include paths.
     * Null = all paths are allowed.
     *
     * @var string[]|null
     */
    protected $allowedIncludePaths = [];

    /**
     * What field sets the client is allowed to request per JSON API resource object type.
     *
     * Null = the client can specify any fields for any resource object type.
     * Empty array = the client cannot specify any fields for any resource object type (i.e. all denied.)
     * Non-empty array = configuration per JSON API resource object type. The key should be the type, the value should
     * be either null (all fields allowed for that type), empty array (no fields allowed for that type) or an array
     * of string values listing the allowed fields for that type.
     *
     * @var array|null
     */
    protected $allowedFieldSetTypes = null;

    /**
     * What sort field names can be sent by the client.
     *
     * Empty array = clients are not allowed to specify sort fields.
     * Null = clients can specify any sort fields.
     *
     * @var string[]|null
     */
    protected $allowedSortFields = [];

    /**
     * What paging fields can be sent by the client.
     *
     * Empty array = clients are not allowed to request paging.
     * Null = clients can specify any paging fields they want.
     *
     * @var string[]|null
     */
    protected $allowedPagingParameters = [];

    /**
     * What filtering fields can be sent by the client.
     *
     * Empty array = clients are not allowed to request filtering.
     * Null = clients can specify any filtering fields they want.
     *
     * @var string[]|null
     */
    protected $allowedFilteringParameters = [];

    /**
     * @var QueryCheckerInterface|null
     */
    private $queryChecker;

    /**
     * @var bool
     */
    private $checkedParameters = false;

    /**
     * @return QueryCheckerInterface
     */
    public function getQueryChecker()
    {
        if (!$this->queryChecker instanceof QueryCheckerInterface) {
            $this->queryChecker = $this->generateQueryChecker();
        }

        return $this->queryChecker;
    }

    /**
     * @return ParametersInterface
     */
    public function getParameters()
    {
        /** @var EnvironmentInterface $environment */
        $environment= App::make(EnvironmentInterface::class);

        return $environment->getParameters();
    }

    /**
     * @return ParametersInterface
     */
    public function checkParameters()
    {
        if (true === $this->checkedParameters) {
            return $this->getParameters();
        }

        $checker = $this->getQueryChecker();
        $params = $this->getParameters();

        $checker->checkQuery($params);

        $this->checkedParameters = true;

        return $params;
    }

    /**
     * @return $this
     */
    public function checkParametersEmpty()
    {
        if (!$this->checkParameters()->isEmpty()) {
            /** @var ExceptionThrowerInterface $thrower */
            $thrower = App::make(ExceptionThrowerInterface::class);
            $thrower->throwBadRequest();
        }

        return $this;
    }

    /**
     * @return QueryCheckerInterface
     */
    private function generateQueryChecker()
    {
        /** @var ParametersFactoryInterface $factory */
        $factory = App::make(ParametersFactoryInterface::class);
        /** @var ExceptionThrowerInterface $thrower */
        $thrower = App::make(ExceptionThrowerInterface::class);

        return $factory->createQueryChecker(
            $thrower,
            $this->allowUnrecognizedParams,
            $this->allowedIncludePaths,
            $this->allowedFieldSetTypes,
            $this->allowedSortFields,
            $this->allowedPagingParameters,
            $this->allowedFilteringParameters
        );
    }
}
