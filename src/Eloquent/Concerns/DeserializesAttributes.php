<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Eloquent\Concerns;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Database\Eloquent\Model;

trait DeserializesAttributes
{

    /**
     * Mapping of JSON API attribute field names to model keys.
     *
     * By default, JSON API attribute fields will automatically be converted to the
     * underscored or camel cased equivalent for the model key. E.g. if the model
     * uses snake case, the JSON API field `published-at` will be converted
     * to `published_at`. If the model does not use snake case, it will be converted
     * to `publishedAt`.
     *
     * For any fields that do not directly convert to model keys, you can list them
     * here. For example, if the JSON API field `published-at` needed to map to the
     * `published_date` model key, then it can be listed as follows:
     *
     * ```php
     * protected $attributes = [
     *   'published-at' => 'published_date',
     * ];
     * ```
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * JSON API fields to skip when filling a model with values from a resource.
     *
     * @var string[]
     */
    protected $guarded = [];

    /**
     * The resource attributes that are dates.
     *
     * A list of JSON API attribute fields that should be cast to dates. If this is
     * empty, then `Model::getDates()` will be used.
     *
     * @var string[]
     */
    protected $dates = [];

    /**
     * Convert a JSON API resource field name to a model key.
     *
     * @param $field
     * @param Model $model
     * @return string
     */
    protected function modelKeyForField($field, $model)
    {
        if (isset($this->attributes[$field])) {
            return $this->attributes[$field];
        }

        $key = $model::$snakeAttributes ? Str::underscore($field) : Str::camelize($field);

        return $this->attributes[$field] = $key;
    }

    /**
     * Deserialize a value obtained from the resource's attributes.
     *
     * @param $value
     *      the value that the client provided.
     * @param $field
     *      the attribute key for the value
     * @param Model $record
     * @return Carbon|null
     */
    protected function deserializeAttribute($value, $field, $record)
    {
        if ($this->isDateAttribute($field, $record)) {
            return $this->deserializeDate($value);
        }

        $method = 'deserialize' . Str::classify($field) . 'Field';

        if (method_exists($this, $method)) {
            return $this->{$method}($value, $record);
        }

        return $value;
    }

    /**
     * Convert a JSON date into a PHP date time object.
     *
     * @param $value
     * @return Carbon|null
     */
    protected function deserializeDate($value)
    {
        return !is_null($value) ? new Carbon($value) : null;
    }

    /**
     * Is this resource key a date attribute?
     *
     * @param $field
     * @param Model $record
     * @return bool
     */
    protected function isDateAttribute($field, $record)
    {
        if (empty($this->dates)) {
            return in_array($this->modelKeyForField($field, $record), $record->getDates(), true);
        }

        return in_array($field, $this->dates, true);
    }

    /**
     * Get the JSON API fields to skip when filling the supplied model.
     *
     * @param Model $record
     * @return string[]
     */
    protected function getGuarded($record)
    {
        return $this->guarded;
    }

    /**
     * Is the JSON API field to be ignored when filling the supplied model?
     *
     * @param $field
     * @param $record
     * @return bool
     */
    protected function isGuarded($field, $record)
    {
        return in_array($field, $this->getGuarded($record));
    }
}