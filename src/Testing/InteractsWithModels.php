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

namespace CloudCreativity\LaravelJsonApi\Testing;

use Illuminate\Database\Eloquent\Model;
use PHPUnit_Framework_Assert as PHPUnit;

/**
 * Class InteractsWithModels
 * @package CloudCreativity\LaravelJsonApi\Testing
 */
trait InteractsWithModels
{

    /**
     * Assert that a model has been created.
     *
     * @param Model $model
     *      a representation of the model that should have been created.
     * @param $expectedResourceId
     *      the expected resource id of the model.
     * @param string|string[]|null $attributeKeys
     *      the keys of the model attributes that should be checked, or null to check all.
     * @param string|null $keyName
     *      the key name to use for the resource id - defaults to `Model::getKeyName()`
     * @return $this
     */
    protected function assertModelCreated(
        Model $model,
        $expectedResourceId,
        $attributeKeys = null,
        $keyName = null
    ) {
        $keyName = $keyName ?: $model->getKeyName();
        $attributes = $model->getAttributes();
        $expected = [$keyName => $expectedResourceId];

        if (is_null($attributeKeys)) {
            $attributeKeys = array_keys($attributes);
        }

        foreach ((array) $attributeKeys as $attr) {
            if ($keyName === $attr) {
                continue;
            }

            $expected[$attr] = isset($attributes[$attr]) ? $attributes[$attr] : null;
        }

        return $this->seeModelInDatabase($model, $expected);
    }

    /**
     * Assert that a model has been patched.
     *
     * @param Model $model
     *      the model before it was patched.
     * @param array $changedAttributes
     *      the expected changed attributes - key to value pairs.
     * @param string|string[] $unchangedKeys
     *      the keys of the attributes that should not have changed.
     * @return $this
     */
    protected function assertModelPatched(Model $model, array $changedAttributes, $unchangedKeys = [])
    {
        /** We need to ensure values are cast to database values */
        $expected = $model->newInstance($changedAttributes)->getAttributes();
        $attributes = $model->getAttributes();

        foreach ((array) $unchangedKeys as $attr) {
            $expected[$attr] = isset($attributes[$attr]) ? $attributes[$attr] : null;
        }

        $expected[$model->getKeyName()] = $model->getKey();

        return $this->seeModelInDatabase($model, $expected);
    }

    /**
     * Assert that a model was deleted.
     *
     * @param Model $model
     * @return $this
     */
    protected function assertModelDeleted(Model $model)
    {
        return $this->notSeeModelInDatabase($model, [$model->getKeyName() => $model->getKey()]);
    }

    /**
     * Assert that a model was soft deleted.
     *
     * @param Model $model
     * @return $this
     */
    protected function assertModelTrashed(Model $model)
    {
        PHPUnit::assertNull($model->fresh(), 'Model is not trashed.');
        return $this->seeModelInDatabase($model, [$model->getKeyName() => $model->getKey()]);
    }

    /**
     * @param Model $model
     * @param array $expected
     * @return $this
     */
    protected function seeModelInDatabase(Model $model, array $expected)
    {
        $message = sprintf(
            'Unable to find model in database table [%s] that matched attributes [%s].',
            $model->getTable(),
            json_encode($expected)
        );

        PHPUnit::assertGreaterThan(0, $this->countModels($model, $expected), $message);

        return $this;
    }

    /**
     * @param Model $model
     * @param array $expected
     * @return $this
     */
    protected function notSeeModelInDatabase(Model $model, array $expected)
    {
        $message = sprintf(
            'Found model in database table [%s] that matched attributes [%s].',
            $model->getTable(),
            json_encode($expected)
        );

        PHPUnit::assertEquals(0, $this->countModels($model, $expected), $message);

        return $this;
    }

    /**
     * @param Model $model
     * @param array $expected
     * @return int
     */
    private function countModels(Model $model, array $expected)
    {
        return $model
            ->getConnection()
            ->table($model->getTable())
            ->where($expected)
            ->count();
    }
}
