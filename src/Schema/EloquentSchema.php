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

namespace CloudCreativity\LaravelJsonApi\Schema;

use Carbon\Carbon;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use DateTime;
use Illuminate\Database\Eloquent\Model;

/**
 * Class EloquentSchema
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class EloquentSchema extends AbstractSchema
{

    /**
     * The attribute to use for the resource id.
     *
     * If null, defaults to `Model::getKeyName()`
     *
     * @var
     */
    protected $idName;

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
     * If null, will default to W3C. In our experience this is the best format to
     * use, but you can easily override it here.
     *
     * @var string|null
     */
    protected $dateFormat;

    /**
     * The model attribute keys to serialize.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Whether resource member names are hyphenated
     *
     * The JSON API spec recommends using hyphens for resource member names, so this package
     * uses this as the default. If you do not want to follow the recommendation, set this
     * to `false`.
     *
     * @var bool
     */
    protected $hyphenated = true;

    /**
     * @param object $resource
     * @return mixed
     */
    public function getId($resource)
    {
        if (!$resource instanceof Model) {
            throw new RuntimeException('Expecting an Eloquent model.');
        }

        $key = $this->idName ?: $resource->getKeyName();

        return $resource->{$key};
    }

    /**
     * @param object $resource
     * @return array
     */
    public function getAttributes($resource)
    {
        if (!$resource instanceof Model) {
            throw new RuntimeException('Expecting an Eloquent model to serialize.');
        }

        return array_merge(
            $this->getDefaultAttributes($resource),
            $this->getModelAttributes($resource)
        );
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
            $defaults[$this->keyForAttribute($createdAt)] = $this->extractAttribute($model, $createdAt);
        }

        if ($this->hasUpdatedAtAttribute($model)) {
            $updatedAt = $model->getUpdatedAtColumn();
            $defaults[$this->keyForAttribute($updatedAt)] = $this->extractAttribute($model, $updatedAt);
        }

        if ($this->hasDeletedAtAttribute($model)) {
            $deletedAt = $model->getDeletedAtColumn();
            $defaults[$this->keyForAttribute($deletedAt)] = $this->extractAttribute($model, $deletedAt);
        }

        return $defaults;
    }

    /**
     * Get attributes for the provided model using fillable attribute.
     *
     * @param Model $model
     * @return array
     */
    protected function getDefaultModelAttributes(Model $model)
    {
        $schemaAttributes = $this->attributes;
        if(empty($schemaAttributes))
        {
            $schemaAttributes = $model->getFillable();
        }
        return $schemaAttributes;
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

        foreach ($this->getDefaultModelAttributes($model) as $modelKey => $attributeKey) {
            if (is_numeric($modelKey)) {
                $modelKey = $attributeKey;
                $attributeKey = $this->keyForAttribute($attributeKey);
            }

            $attributes[$attributeKey] = $this->extractAttribute($model, $modelKey);
        }

        return $attributes;
    }

    /**
     * Convert a model key into a resource attribute key.
     *
     * @param $modelKey
     * @return string
     */
    protected function keyForAttribute($modelKey)
    {
        return $this->hyphenated ? Str::dasherize($modelKey) : $modelKey;
    }

    /**
     * @param Model $model
     * @param $modelKey
     * @return string
     */
    protected function extractAttribute(Model $model, $modelKey)
    {
        $value = $model->{$modelKey};

        return $this->serializeAttribute($value, $model, $modelKey);
    }

    /**
     * @param $value
     * @param Model $model
     * @param $modelKey
     * @return string
     */
    protected function serializeAttribute($value, Model $model, $modelKey)
    {
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
