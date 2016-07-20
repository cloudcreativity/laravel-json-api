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

namespace CloudCreativity\LaravelJsonApi\Document;

use CloudCreativity\LaravelJsonApi\Contracts\Document\LinkFactoryInterface;

/**
 * Class GeneratesRouteNames
 * @package CloudCreativity\LaravelJsonApi
 */
trait GeneratesRouteNames
{

    /**
     * @param $resourceType
     * @return string
     */
    protected function indexRouteName($resourceType)
    {
        return sprintf(LinkFactoryInterface::ROUTE_NAME_INDEX, $resourceType);
    }

    /**
     * @param $resourceType
     * @return string
     */
    protected function resourceRouteName($resourceType)
    {
        return sprintf(LinkFactoryInterface::ROUTE_NAME_RESOURCE, $resourceType);
    }

    /**
     * @param $resourceType
     * @return string
     */
    protected function relatedResourceRouteName($resourceType)
    {
        return sprintf(LinkFactoryInterface::ROUTE_NAME_RELATED_RESOURCE, $resourceType);
    }

    /**
     * @param $resourceType
     * @return string
     */
    protected function relationshipRouteName($resourceType)
    {
        return sprintf(LinkFactoryInterface::ROUTE_NAME_RELATIONSHIPS, $resourceType);
    }
}
