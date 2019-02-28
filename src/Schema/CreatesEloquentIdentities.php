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

namespace CloudCreativity\LaravelJsonApi\Schema;

use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Trait CreatesEloquentIdentities
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0
 */
trait CreatesEloquentIdentities
{

    /**
     * Convert a belongs to relationship without causing the database to be queried.
     *
     * This should only be used when the related model is not going to be included in the
     * JSON API response to the client. In those circumstances, it is more efficient than
     * returning the actual related model because there will be no database query to
     * obtain the full related model.
     *
     * @param Model $model
     * @param $relationshipKey
     * @return Model
     * @deprecated
     */
    protected function createBelongsToIdentity(Model $model, $relationshipKey)
    {
        $relation = $model->{$relationshipKey}();

        if (!$relation instanceof BelongsTo) {
            throw new RuntimeException(sprintf(
                'Expecting %s on %s to be a belongs-to relationship.',
                $relationshipKey,
                get_class($model)
            ));
        }

        // support Laravel 5.8
        $foreignKey = method_exists($relation, 'getForeignKeyName') ?
            $relation->getForeignKeyName() :
            $relation->getForeignKey();

        $id = $model->{$foreignKey};

        if (is_null($id)) {
            return null;
        }

        // support Laravel 5.8
        $ownerKey = method_exists($relation, 'getOwnerKeyName') ?
            $relation->getOwnerKeyName() :
            $relation->getOwnerKey();

        $related = $relation->getRelated()->replicate();
        $related->{$ownerKey} = $id;

        return $related;
    }

    /**
     * Create a model identity using the model class and a provided id.
     *
     * @param $modelClass
     * @param string|int|null $id
     * @param string|null $keyName
     *      the key to set as the id - defaults to `Model::getRouteKeyName()`
     * @return Model|null
     * @deprecated
     */
    protected function createModelIdentity(
        $modelClass,
        $id,
        $keyName = null
    ) {
        if (is_null($id)) {
            return null;
        }

        $model = new $modelClass();

        if (!$model instanceof Model) {
            throw new RuntimeException(sprintf('Expecting a model class, got %s.', $modelClass));
        }

        $model->setAttribute($keyName ?: $model->getRouteKeyName(), $id);

        return $model;
    }
}
