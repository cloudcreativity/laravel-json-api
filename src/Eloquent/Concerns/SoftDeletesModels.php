<?php

namespace CloudCreativity\LaravelJsonApi\Eloquent\Concerns;

use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Trait SoftDeletesModels
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait SoftDeletesModels
{

    /**
     * The JSON API field name that is used for the soft delete value.
     *
     * If none is set, defaults to the dasherized version of the model's
     * deleted_at column.
     *
     * @var string|null
     */
    protected $softDeleteField = null;

    /**
     * @param Model $record
     * @param EncodingParametersInterface $params
     * @return bool
     */
    public function delete($record, EncodingParametersInterface $params)
    {
        return (bool) $record->forceDelete();
    }

    /**
     * @param $resourceId
     * @return Builder
     */
    protected function findQuery($resourceId)
    {
        return $this->newQuery()->withTrashed()->where(
            $this->getQualifiedKeyName(),
            $resourceId
        );
    }

    /**
     * @param Model $record
     * @param Collection $attributes
     */
    protected function fillAttributes($record, Collection $attributes)
    {
        $record->fill(
            $this->deserializeAttributes($attributes, $record)
        );

        $this->fillSoftDelete($record, $attributes);
    }

    /**
     * Fill the soft delete value if it is been provided.
     *
     * @param Model $record
     * @param Collection $attributes
     */
    protected function fillSoftDelete(Model $record, Collection $attributes)
    {
        $key = $this->getSoftDeleteKey($record);
        $field = $this->getSoftDeleteField($record);

        if (!$field || !$key || !$attributes->has($field)) {
            return;
        }

        $value = $attributes->get($field);

        $record->forceFill([
            $key => $this->deserializeAttribute($value, $field, $record)
        ]);
    }

    /**
     * Get the JSON API field that is used for the soft-delete value.
     *
     * @param Model $record
     * @return string|null
     */
    protected function getSoftDeleteField(Model $record)
    {
        if ($this->softDeleteField) {
            return $this->softDeleteField;
        }

        if ($key = $this->getSoftDeleteKey($record)) {
            return $this->softDeleteField = Str::dasherize($key);
        }

        return null;
    }

    /**
     * Get the model key that should be used for the soft-delete value.
     *
     * @param Model $record
     * @return string|null
     */
    protected function getSoftDeleteKey(Model $record)
    {
        return method_exists($record, 'getDeletedAtColumn') ? $record->getDeletedAtColumn() : null;
    }

    /**
     * @param $record
     * @return void
     */
    protected function persist($record)
    {
        $this->saveOrRestore($record);
    }

    /**
     * @param Model $record
     */
    protected function saveOrRestore(Model $record)
    {
        if ($this->willRestore($record)) {
           $record->restore();
       } else {
            $record->save();
        }
    }

    /**
     * @param Model $record
     * @return bool
     */
    protected function willRestore(Model $record)
    {
        if (!$record->exists) {
            return false;
        }

        if (!$key = $this->getSoftDeleteKey($record)) {
            return false;
        }

        return $record->isDirty($key) && !$record->trashed();
    }

}
