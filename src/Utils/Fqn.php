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

use CloudCreativity\JsonApi\Utils\Str;

/**
 * Class Fqn
 *
 * @package CloudCreativity\LaravelJsonApi\Utils
 */
class Fqn
{

    /**
     * @param $resourceType
     * @param $rootNamespace
     * @param bool $byResource
     * @return string
     */
    public static function schema($resourceType, $rootNamespace, $byResource = true)
    {
        return self::fqn('Schema', $resourceType, $rootNamespace, $byResource);
    }

    /**
     * @param $resourceType
     * @param $rootNamespace
     * @param bool $byResource
     * @return string
     */
    public static function adapter($resourceType, $rootNamespace, $byResource = true)
    {
        return self::fqn('Adapter', $resourceType, $rootNamespace, $byResource);
    }

    /**
     * @param $resourceType
     * @param $rootNamespace
     * @param bool $byResource
     * @return string
     */
    public static function authorizer($resourceType, $rootNamespace, $byResource = true)
    {
        return self::fqn('Authorizer', $resourceType, $rootNamespace, $byResource);
    }

    /**
     * @param $resourceType
     * @param $rootNamespace
     * @param bool $byResource
     * @return string
     */
    public static function validators($resourceType, $rootNamespace, $byResource = true)
    {
        return self::fqn('Validators', $resourceType, $rootNamespace, $byResource);
    }

    /**
     * @param $type
     * @param $resourceType
     * @param $rootNamespace
     * @param $byResource
     * @return string
     */
    private static function fqn($type, $resourceType, $rootNamespace, $byResource)
    {
        $resourceType = Str::classify($resourceType);

        if ($byResource) {
            return sprintf('%s\%s\%s', $rootNamespace, $resourceType, $type);
        }

        return sprintf('%s\%s\%s', $rootNamespace, str_plural($type), str_singular($resourceType));
    }
}
