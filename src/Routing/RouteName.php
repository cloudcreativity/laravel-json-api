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

namespace CloudCreativity\LaravelJsonApi\Routing;

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
     * @param $relationship
     * @return string
     */
    public static function related($resourceType, $relationship)
    {
        return "$resourceType.relationships.$relationship";
    }

    /**
     * @param $resourceType
     * @param $relationship
     * @return string
     */
    public static function readRelationship($resourceType, $relationship)
    {
        return self::related($resourceType, $relationship) . ".read";
    }

    /**
     * @param $resourceType
     * @param $relationship
     * @return string
     */
    public static function replaceRelationship($resourceType, $relationship)
    {
        return self::related($resourceType, $relationship) . ".replace";
    }

    /**
     * @param $resourceType
     * @param $relationship
     * @return string
     */
    public static function addRelationship($resourceType, $relationship)
    {
        return self::related($resourceType, $relationship) . ".add";
    }

    /**
     * @param $resourceType
     * @param $relationship
     * @return string
     */
    public static function removeRelationship($resourceType, $relationship)
    {
        return self::related($resourceType, $relationship) . ".remove";
    }
}
