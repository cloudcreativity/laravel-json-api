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

namespace CloudCreativity\LaravelJsonApi\Document;

use CloudCreativity\LaravelJsonApi\Utils\RouteName;

/**
 * Class GeneratesRouteNames
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated
 */
trait GeneratesRouteNames
{

    /**
     * @param $resourceType
     * @return string
     * @deprecated use `RouteName::index`
     */
    protected function indexRouteName($resourceType)
    {
        return RouteName::index($resourceType);
    }

    /**
     * @param $resourceType
     * @return string
     * @deprecated use `RouteName::resource`
     */
    protected function resourceRouteName($resourceType)
    {
        return RouteName::read($resourceType);
    }

    /**
     * @param $resourceType
     * @return string
     * @deprecated use `RouteName::related`
     */
    protected function relatedResourceRouteName($resourceType)
    {
        return RouteName::related($resourceType);
    }

    /**
     * @param $resourceType
     * @return string
     * @deprecated use `RouteName::relationship`
     */
    protected function relationshipRouteName($resourceType)
    {
        return RouteName::readRelationship($resourceType);
    }
}
