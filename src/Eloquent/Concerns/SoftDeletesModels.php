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

namespace CloudCreativity\LaravelJsonApi\Eloquent\Concerns;

use Carbon\Carbon;
use CloudCreativity\LaravelJsonApi\Utils\Str;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

/**
 * Trait SoftDeletesModels
 *
 * @package CloudCreativity\LaravelJsonApi
 */
trait SoftDeletesModels
{

    /**
     * @param Model $record
     * @return bool
     */
    protected function destroy($record)
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
        $field = $this->getSoftDeleteField($record);
        $attributesArr = $attributes->toArray();

        if (Arr::has($attributesArr, $field)) {
            $this->fillSoftDelete($record, $field, Arr::get($attributesArr, $field));
        }

        $record->fill(
            $this->deserializeAttributes(Arr::except($attributesArr, $field), $record)
        );
    }

    /**
     * Fill the soft delete value if it has been provided.
     *
     * @param Model $record
     * @param string $field
     * @param mixed $value
     */
    protected function fillSoftDelete(Model $record, $field, $value)
    {
        $value = $this->deserializeSoftDelete(
            $value,
            $field,
            $record
        );

        $record->forceFill([
            $this->getSoftDeleteKey($record) => $value,
        ]);
    }

    /**
     * Deserialize the provided value for the soft delete attribute.
     *
     * If a boolean is provided, we interpret the soft delete value as now.
     * We check for all the boolean values accepted by the Laravel boolean
     * validator.
     *
     * @param $value
     * @param $field
     * @param $record
     * @return Carbon|null
     */
    protected function deserializeSoftDelete($value, $field, $record)
    {
        if (collect([true, false, 1, 0, '1', '0'])->containsStrict($value)) {
            return $value ? Carbon::now() : null;
        }

        return $this->deserializeAttribute($value, $field, $record);
    }

    /**
     * The JSON API field name that is used for the soft delete value.
     *
     * If none is set, defaults to the camel-case version of the model's
     * `deleted_at` column, e.g. `deletedAt`.
     *
     * @return string|null
     */
    protected function softDeleteField()
    {
        return property_exists($this, 'softDeleteField') ? $this->softDeleteField : null;
    }

    /**
     * Get the JSON API field that is used for the soft-delete value.
     *
     * @param Model $record
     * @return string
     */
    protected function getSoftDeleteField(Model $record)
    {
        if ($field = $this->softDeleteField()) {
            return $field;
        }

        $key = $this->getSoftDeleteKey($record);

        return Str::camelize($key);
    }

    /**
     * Get the model key that should be used for the soft-delete value.
     *
     * @param Model $record
     * @return string
     */
    protected function getSoftDeleteKey(Model $record)
    {
        return $record->getDeletedAtColumn();
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
     * @return void
     */
    protected function saveOrRestore(Model $record)
    {
        if ($this->willRestore($record)) {
           $record->restore();
           return;
       }

        /**
         * To ensure Laravel still executes its soft-delete logic (e.g. firing events)
         * we need to delete before a save when we are soft-deleting. Although this
         * may result in two database calls in this scenario, it means we can guarantee
         * that standard Laravel soft-delete logic is executed.
         *
         * @see https://github.com/cloudcreativity/laravel-json-api/issues/371
         */
        if ($this->willSoftDelete($record)) {
            $key = $this->getSoftDeleteKey($record);
            // save the original date so we can put it back later on
            $deletedAt = $record->{$key};
            // delete on the record so that deleting and deleted events get fired
            $record->delete();
            // apply the original soft deleting date back before saving
            $record->{$key} = $deletedAt;
        }

        $record->save();
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

        /**
         * The use of `trashed()` here looks the wrong way round, but it is
         * because that method checks the current value on the model. I.e.
         * as we have filled the model by this point, it will think that it
         * is not trashed even though that has not been persisted to the database.
         */
        return $record->isDirty($key) && !$record->trashed();
    }

    /**
     * @param Model $record
     * @return bool
     */
    protected function willSoftDelete(Model $record)
    {
        if (!$record->exists) {
            return false;
        }

        if (!$key = $this->getSoftDeleteKey($record)) {
            return false;
        }

        return null === $record->getOriginal($key) && $record->trashed();
    }

}
