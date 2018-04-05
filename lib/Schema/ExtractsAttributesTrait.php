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

namespace CloudCreativity\JsonApi\Schema;

use CloudCreativity\JsonApi\Utils\Str;
use DateTime;

/**
 * Class ExtractsAttributesTrait
 *
 * @package CloudCreativity\JsonApi
 */
trait ExtractsAttributesTrait
{

    /**
     * @param $record
     *      the record being serialized
     * @param $recordKey
     *      the key to extract
     * @return mixed
     */
    abstract protected function extractAttribute($record, $recordKey);

    /**
     * Get attributes for the provided record.
     *
     * @param object $record
     * @return array
     */
    public function getAttributes($record)
    {
        $attributes = [];

        foreach ($this->attributeKeys($record) as $recordKey => $attributeKey) {
            if (is_numeric($recordKey)) {
                $recordKey = $attributeKey;
                $attributeKey = $this->keyForAttribute($attributeKey);
            }

            $value = $this->extractAttribute($record, $recordKey);
            $attributes[$attributeKey] = $this->serializeAttribute($value, $record, $recordKey);
        }

        return $attributes;
    }

    /**
     * Get a list of attributes that are to be extracted.
     *
     * @param object $record
     * @return array
     */
    protected function attributeKeys($record)
    {
        $keys = property_exists($this, 'attributes') ? $this->attributes : null;

        if (is_null($keys)) {
            return array_keys(get_object_vars($record));
        }

        return (array) $keys;
    }

    /**
     * Convert a record key into a resource attribute key.
     *
     * @param $recordKey
     * @return string
     */
    protected function keyForAttribute($recordKey)
    {
        $dasherized = property_exists($this, 'dasherize') ? $this->dasherize : true;

        return $dasherized ? Str::dasherize($recordKey) : $recordKey;
    }

    /**
     * @param $value
     * @param object $record
     * @param $recordKey
     * @return string
     */
    protected function serializeAttribute($value, $record, $recordKey)
    {
        if (method_exists($this, $method = $this->methodForSerializer($recordKey))) {
            $value = call_user_func([$this, $method], $value, $record);
        }

        if ($value instanceof DateTime) {
            $value = $this->serializeDateTime($value, $record);
        }

        return $value;
    }

    /**
     * @param DateTime $value
     * @param object $record
     * @return string
     */
    protected function serializeDateTime(DateTime $value, $record)
    {
        return $value->format($this->dateFormat());
    }

    /**
     * @return string
     */
    protected function dateFormat()
    {
        $format = property_exists($this, 'dateFormat') ? $this->dateFormat : null;

        return $format ?: DateTime::W3C;
    }

    /**
     * @param $recordKey
     * @return string
     */
    protected function methodForSerializer($recordKey)
    {
        return 'serialize' . Str::classify($recordKey) . 'Attribute';
    }
}
