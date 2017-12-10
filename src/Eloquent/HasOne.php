<?php
/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Eloquent;

use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class HasOne
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class HasOne extends AbstractRelation
{

    /**
     * @param Model $record
     * @param EncodingParametersInterface $parameters
     * @return mixed
     */
    public function query($record, EncodingParametersInterface $parameters)
    {
        return $record->{$this->key};
    }

    /**
     * @param Model $record
     * @param EncodingParametersInterface $parameters
     * @return mixed
     */
    public function relationship($record, EncodingParametersInterface $parameters)
    {
        return $this->query($record, $parameters);
    }

    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    public function update($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        /** @var BelongsTo $relation */
        $relation = $record->{$this->key}();
        $identifier = $relationship->hasIdentifier() ? $relationship->getIdentifier() : null;
        $related = $identifier ? $this->store()->find($identifier) : null;

        if ($related) {
            $relation->associate($related);
        } else {
            $relation->dissociate();
        }
    }

    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return Model
     */
    public function replace($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $this->update($record, $relationship, $parameters);
        $record->save();

        return $record;
    }

}
