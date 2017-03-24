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

namespace CloudCreativity\LaravelJsonApi\Utils;

/**
 * Class RouteName
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class RouteName
{

    /**
     * @param $resourceType
     * @return string
     */
    public static function index($resourceType)
    {
        return "$resourceType.index";
    }

    /**
     * @param $resourceType
     * @return string
     */
    public static function create($resourceType)
    {
        return "$resourceType.create";
    }

    /**
     * @param $resourceType
     * @return string
     */
    public static function read($resourceType)
    {
        return "$resourceType.read";
    }

    /**
     * @param $resourceType
     * @return string
     */
    public static function update($resourceType)
    {
        return "$resourceType.update";
    }

    /**
     * @param $resourceType
     * @return string
     */
    public static function delete($resourceType)
    {
        return "$resourceType.delete";
    }

    /**
     * @param $resourceType
     * @return string
     */
    public static function related($resourceType)
    {
        return "$resourceType.related";
    }

    /**
     * @param $resourceType
     * @return string
     */
    public static function readRelationship($resourceType)
    {
        return "$resourceType.relationships.read";
    }

    /**
     * @param $resourceType
     * @return string
     */
    public static function replaceRelationship($resourceType)
    {
        return "$resourceType.relationships.replace";
    }

    /**
     * @param $resourceType
     * @return string
     */
    public static function addRelationship($resourceType)
    {
        return "$resourceType.relationships.add";
    }

    /**
     * @param $resourceType
     * @return string
     */
    public static function removeRelationship($resourceType)
    {
        return "$resourceType.relationships.remove";
    }
}
