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

namespace CloudCreativity\JsonApi\Utils;

use Neomerx\JsonApi\Contracts\Document\DocumentInterface as Keys;

/**
 * Class PointerUtils
 *
 * @package CloudCreativity\JsonApi
 */
class Pointer
{

    /**
     * @return string
     */
    public static function root()
    {
        return '/';
    }

    /**
     * @return string
     */
    public static function data()
    {
        return self::root() . Keys::KEYWORD_DATA;
    }

    /**
     * @return string
     */
    public static function type()
    {
        return self::dataType();
    }

    /**
     * @return string
     */
    public static function dataType()
    {
        return sprintf('%s/%s', self::data(), Keys::KEYWORD_TYPE);
    }

    /**
     * @return string
     */
    public static function id()
    {
        return self::dataId();
    }

    /**
     * @return string
     */
    public static function dataId()
    {
        return sprintf('%s/%s', self::data(), Keys::KEYWORD_ID);
    }

    /**
     * @return string
     */
    public static function attributes()
    {
        return self::dataAttributes();
    }

    /**
     * @return string
     */
    public static function dataAttributes()
    {
        return sprintf('%s/%s', self::data(), Keys::KEYWORD_ATTRIBUTES);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function attribute($name)
    {
        return self::dataAttribute($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function dataAttribute($name)
    {
        return sprintf('%s/%s', self::dataAttributes(), $name);
    }

    /**
     * @return string
     */
    public static function relationships()
    {
        return self::dataRelationships();
    }

    /**
     * @return string
     */
    public static function dataRelationships()
    {
        return sprintf('%s/%s', self::data(), Keys::KEYWORD_RELATIONSHIPS);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function relationship($name)
    {
        return self::dataRelationship($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function dataRelationship($name)
    {
        return sprintf('%s/%s', self::dataRelationships(), $name);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function relationshipData($name)
    {
        return self::dataRelationshipData($name);
    }

    /**
     * @param $name
     * @return string
     */
    public static function dataRelationshipData($name)
    {
        return sprintf('%s/%s', self::dataRelationship($name), Keys::KEYWORD_DATA);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function relationshipType($name)
    {
        return self::dataRelationshipDataType($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function dataRelationshipDataType($name)
    {
        return sprintf('%s/%s', self::dataRelationshipData($name), Keys::KEYWORD_TYPE);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function relationshipId($name)
    {
        return self::dataRelationshipDataId($name);
    }

    /**
     * @param string $name
     * @return string
     */
    public static function dataRelationshipDataId($name)
    {
        return sprintf('%s/%s', self::dataRelationshipData($name), Keys::KEYWORD_ID);
    }
}
