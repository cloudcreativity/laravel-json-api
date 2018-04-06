<?php

namespace CloudCreativity\LaravelJsonApi\Eloquent\Concerns;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use DateTime;
use Illuminate\Database\Eloquent\Model;

trait SerializesAttributes
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
            $defaults[$this->fieldForAttribute($createdAt)] = $this->extractAttribute($model, $createdAt);
        }

        if ($this->hasUpdatedAtAttribute($model)) {
            $updatedAt = $model->getUpdatedAtColumn();
            $defaults[$this->fieldForAttribute($updatedAt)] = $this->extractAttribute($model, $updatedAt);
        }

        if ($this->hasDeletedAtAttribute($model)) {
            $deletedAt = $model->getDeletedAtColumn();
            $defaults[$this->fieldForAttribute($deletedAt)] = $this->extractAttribute($model, $deletedAt);
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

            $attributes[$field] = $this->extractAttribute($model, $modelKey);
        }

        return $attributes;
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

        $field = $this->fieldForAttribute($modelKey);
        $method = 'serialize' . Str::classify($field) . 'Field';

        if (method_exists($this, $method)) {
            return $this->{$method}($value, $model);
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