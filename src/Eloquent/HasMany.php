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

use CloudCreativity\JsonApi\Contracts\Adapter\HasManyAdapterInterface;
use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class HasMany
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class HasMany extends AbstractRelation implements HasManyAdapterInterface
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
        $related = $this->store()->findMany($relationship->getIdentifiers());

        $this->getRelation($record)->sync(new Collection($related));
        // do not refresh as we expect the resource adapter to refresh the record.
    }

    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    public function replace($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $this->update($record, $relationship, $parameters);
        $record->refresh(); // in case the relationship has been cached.
    }

    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    public function add($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $related = $this->store()->findMany($relationship->getIdentifiers());

        $this->getRelation($record)->attach(new Collection($related));
    }

    /**
     * @param Model $record
     * @param RelationshipInterface $relationship
     * @param EncodingParametersInterface $parameters
     * @return void
     */
    public function remove($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        $related = $this->store()->findMany($relationship->getIdentifiers());

        $this->getRelation($record)->detach(new Collection($related));
    }

    /**
     * @param Model $record
     * @return BelongsToMany
     */
    protected function getRelation($record)
    {
        $relation = $record->{$this->key}();

        if (!$relation instanceof BelongsToMany) {
            throw new RuntimeException("Expecting a belongs-to-many relationship.");
        }

        return $relation;
    }

}
