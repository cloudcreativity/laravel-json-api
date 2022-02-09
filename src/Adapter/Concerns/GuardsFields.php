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

namespace CloudCreativity\LaravelJsonApi\Adapter\Concerns;

/**
 * Trait GuardsFields
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait GuardsFields
{

    /**
     * JSON API fields that are fillable into a record.
     *
     * @var string[]
     */
    protected $fillable = [];

    /**
     * JSON API fields to skip when filling a record with values from a resource.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * Is the JSON API field allowed to be filled into the supplied record?
     *
     * @param $field
     * @param $record
     * @return bool
     */
    protected function isFillable($field, $record)
    {
        /** If the field is listed in the fillable fields, it can be filled. */
        if (in_array($field, $fillable = $this->getFillable($record))) {
            return true;
        }

        /** If the field is listed in the guarded fields, it cannot be filled. */
        if ($this->isGuarded($field, $record)) {
            return false;
        }

        /** Otherwise we can fill if everything is fillable. */
        return empty($fillable);
    }

    /**
     * Is the JSON API field not allowed to be filled into the supplied record?
     *
     * @param $field
     * @param $record
     * @return bool
     */
    protected function isNotFillable($field, $record)
    {
        return !$this->isFillable($field, $record);
    }

    /**
     * Is the JSON API field to be ignored when filling the supplied record?
     *
     * @param $field
     * @param $record
     * @return bool
     */
    protected function isGuarded($field, $record)
    {
        return in_array($field, $this->getGuarded($record));
    }

    /**
     * Get the JSON API fields that are allowed to be filled into a record.
     *
     * @param $record
     * @return string[]
     */
    protected function getFillable($record)
    {
        return $this->fillable;
    }

    /**
     * Get the JSON API fields to skip when filling the supplied record.
     *
     * @param $record
     * @return string[]
     */
    protected function getGuarded($record)
    {
        return $this->guarded;
    }

}
