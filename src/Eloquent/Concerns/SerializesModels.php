<?php
/**
 * Copyright 2019 Cloud Creativity Limited
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
use Closure;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Neomerx\JsonApi\Schema\SchemaProvider;

/**
 * Trait SerializesModels
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0
 */
trait SerializesModels
{

    /**
     * Should the created at attribute be included?
     *
     * If the model does not have timestamps, then this setting will be ignored.
     *
     * @var bool
     */
    protected $createdAt = true;

    /**
     * Should the updated at attribute be included?
     *
     * If the model does not have timestamps, then this setting will be ignored.
     *
     * @var bool
     */
    protected $updatedAt = true;

    /**
     * Should the deleted at attribute be included?
     *
     * If the model does not use the `SoftDeletes` trait, this will be ignored.
     *
     * @var bool
     */
    protected $deletedAt = true;

    /**
     * The date format to use.
     *
     * @var string
     */
    protected $dateFormat = Carbon::W3C;

    /**
     * Whether resource field names are hyphenated
     *
     * The JSON API spec recommends using hyphens for resource member names, so this package
     * uses this as the default. If you do not want to follow the recommendation, set this
     * to `false`.
     *
     * @var bool
     */
    protected $hyphenated = true;

    /**
     * The model attribute keys to serialize.
     *
     * - Empty array: no attributes to serialize.
     * - Non-empty array: serialize the specified model keys.
     * - Null: serialize the model's visible keys.
     *
     * @var array|null
     */
    protected $attributes = null;

    /**
     * The model relationships to serialize.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * @param Model $record
     * @param bool $isPrimary
     * @param array $includedRelationships
     * @return array
     */
    public function getRelationships($record, $isPrimary, array $includedRelationships)
    {
        $relations = [];

        foreach ($this->relationshipKeys($record) as $modelKey => $field) {
            if (is_numeric($modelKey)) {
                $modelKey = $field;
                $field = $this->fieldForRelationship($field);
            }

            $relations[$field] = $this->getRelation($record, $modelKey, $field, $includedRelationships);
        }

        return $relations;
    }

    /**
     * Get attributes that are included for every model class.
     *
     * @param Model $model
     * @return array
     */
    protected function getDefaultAttributes(Model $model)
    {
        $defaults = [];

        if ($this->hasCreatedAtAttribute($model)) {
            $createdAt = $model->getCreatedAtColumn();
            $field = $this->fieldForAttribute($createdAt);
            $defaults[$field] = $this->extractAttribute($model, $createdAt, $field);
        }

        if ($this->hasUpdatedAtAttribute($model)) {
            $updatedAt = $model->getUpdatedAtColumn();
            $field = $this->fieldForAttribute($updatedAt);
            $defaults[$field] = $this->extractAttribute($model, $updatedAt, $field);
        }

        if ($this->hasDeletedAtAttribute($model)) {
            $deletedAt = $model->getDeletedAtColumn();
            $field = $this->fieldForAttribute($deletedAt);
            $defaults[$field] = $this->extractAttribute($model, $deletedAt, $field);
        }

        return $defaults;
    }

    /**
     * Get attributes for the provided model.
     *
     * @param Model $model
     * @return array
     */
    protected function getModelAttributes(Model $model)
    {
        $attributes = [];

        foreach ($this->attributeKeys($model) as $modelKey => $field) {
            if (is_numeric($modelKey)) {
                $modelKey = $field;
                $field = $this->fieldForAttribute($field);
            }

            $attributes[$field] = $this->extractAttribute($model, $modelKey, $field);
        }

        return $attributes;
    }

    /**
     * @param Model $model
     * @param string $modelKey
     * @param string $field
     * @param array $includedRelationships
     * @return array
     */
    protected function getRelation($model, $modelKey, $field, array $includedRelationships)
    {
        return [
            SchemaProvider::SHOW_SELF => true,
            SchemaProvider::SHOW_RELATED => true,
            SchemaProvider::SHOW_DATA => isset($includedRelationships[$field]),
            SchemaProvider::DATA => $this->extractRelationship($model, $modelKey),
        ];
    }

    /**
     * Get the attributes to serialize for the provided model.
     *
     * @param Model $model
     * @return array
     */
    protected function attributeKeys(Model $model)
    {
        if (is_array($this->attributes)) {
            return $this->attributes;
        }

        return $model->getVisible();
    }

    /**
     * @param $model
     * @return array
     */
    protected function relationshipKeys($model)
    {
        return $this->relationships;
    }

    /**
     * Convert a model key into a resource field name.
     *
     * @param $modelKey
     * @return string
     */
    protected function fieldForAttribute($modelKey)
    {
        return $this->hyphenated ? Str::dasherize($modelKey) : $modelKey;
    }

    /**
     * @param $modelKey
     * @return string
     */
    protected function fieldForRelationship($modelKey)
    {
        return $this->fieldForAttribute($modelKey);
    }

    /**
     * @param Model $model
     * @param $modelKey
     * @param $field
     * @return string
     */
    protected function extractAttribute(Model $model, $modelKey, $field)
    {
        $value = $model->{$modelKey};

        return $this->serializeAttribute($value, $model, $modelKey, $field);
    }

    /**
     * @param $model
     * @param $modelKey
     * @return Closure
     */
    protected function extractRelationship($model, $modelKey)
    {
        return function () use ($model, $modelKey) {
            return $model->{$modelKey};
        };
    }

    /**
     * @param $value
     * @param Model $model
     * @param $modelKey
     * @param $field
     * @return string
     */
    protected function serializeAttribute($value, Model $model, $modelKey, $field)
    {
        $method = 'serialize' . Str::classify($field) . 'Field';

        if (method_exists($this, $method)) {
            return $this->{$method}($value, $model);
        }

        if ($value instanceof DateTime) {
            $value = $this->serializeDateTime($value, $model);
        }

        return $value;
    }

    /**
     * @param DateTime $value
     * @param Model $model
     * @return string
     */
    protected function serializeDateTime(DateTime $value, Model $model)
    {
        return $value->format($this->getDateFormat());
    }

    /**
     * @param Model $model
     * @return bool
     */
    protected function hasCreatedAtAttribute(Model $model)
    {
        return $model->timestamps && true === $this->createdAt;
    }

    /**
     * @param Model $model
     * @return bool
     */
    protected function hasUpdatedAtAttribute(Model $model)
    {
        return $model->timestamps && true === $this->updatedAt;
    }

    /**
     * @param Model $model
     * @return bool
     */
    protected function hasDeletedAtAttribute(Model $model)
    {
        return true === $this->deletedAt && method_exists($model, 'getDeletedAtColumn');
    }

    /**
     * @return string
     */
    protected function getDateFormat()
    {
        return $this->dateFormat ?: Carbon::W3C;
    }
}
