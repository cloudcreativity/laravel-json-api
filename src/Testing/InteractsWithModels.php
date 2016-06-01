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
use Illuminate\Foundation\Testing\Concerns\InteractsWithDatabase;

/**
 * Class InteractsWithModels
 * @package CloudCreativity\LaravelJsonApi\Testing
 */
trait InteractsWithModels
{

    use InteractsWithDatabase;

    /**
     * Assert that a model has been created.
     *
     * @param Model $model
     *      a representation of the model that should have been created.
     * @param $expectedId
     *      the expected id of the model.
     * @param string|string[] $attributeKeys
     *      the keys of the model attributes that should be checked.
     * @param string|null $keyName
     *      the key name to use for the id - defaults to `Model::getKeyName()`
     */
    public function assertModelCreated(
        Model $model,
        $expectedId,
        $attributeKeys = [],
        $keyName = null
    ) {
        if (!$keyName) {
            $keyName = $model->getKeyName();
        }

        $expected = [$keyName => $expectedId];

        foreach ((array) $attributeKeys as $attr) {
            $expected[$attr] = $model->{$attr};
        }

        $this->seeInDatabase($model->getTable(), $expected, $model->getConnectionName());
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
     */
    public function assertModelPatched(Model $model, array $changedAttributes, $unchangedKeys = [])
    {
        $expected = $changedAttributes;

        foreach ((array) $unchangedKeys as $attr) {
            $expected[$attr] = $model->{$attr};
        }

        $expected[$model->getKeyName()] = $model->getKey();
        $this->seeInDatabase($model->getTable(), $expected, $model->getConnectionName());
    }

    /**
     * Assert that a model was deleted.
     *
     * @param Model $model
     */
    public function assertModelDeleted(Model $model)
    {
        $this->notSeeInDatabase($model->getTable(), [
            $model->getKeyName() => $model->getKey()
        ], $model->getConnectionName());
    }
}
