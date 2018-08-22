<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Contracts\Adapter\RelationshipAdapterInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use CloudCreativity\LaravelJsonApi\Store\StoreAwareTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class AbstractRelation
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractRelation implements RelationshipAdapterInterface, StoreAwareInterface
{

    use StoreAwareTrait;

    /**
     * @var Model
     */
    protected $model;

    /**
     * The model key.
     *
     * @var string
     */
    protected $key;

    /**
     * @var string|null
     */
    protected $field;

    /**
     * Is the supplied Eloquent relation acceptable for this JSON API relation?
     *
     * @param Relation $relation
     * @return bool
     */
    abstract protected function acceptRelation($relation);

    /**
     * AbstractRelation constructor.
     *
     * @param Model $model
     * @param $key
     */
    public function __construct(Model $model, $key)
    {
        $this->model = $model;
        $this->key = $key;
    }

    /**
     * @inheritdoc
     */
    public function withFieldName($name)
    {
        $this->field = $name;

        return $this;
    }

    /**
     * Get the relation from the model.
     *
     * @param Model $record
     * @return Relation
     */
    protected function getRelation($record)
    {
        $relation = $record->{$this->key}();

        if (!$this->acceptRelation($relation)) {
            throw new RuntimeException(sprintf(
                'JSON API relation %s cannot be used for an Eloquent %s relation.',
                class_basename($this),
                class_basename($relation)
            ));
        }

        return $relation;
    }

    /**
     * Does the query need to be passed to the inverse adapter?
     *
     * @param $record
     * @param EncodingParametersInterface $parameters
     * @return bool
     */
    protected function requiresInverseAdapter($record, EncodingParametersInterface $parameters)
    {
        return !empty($parameters->getFilteringParameters()) ||
            !empty($parameters->getSortParameters()) ||
            !empty($parameters->getPaginationParameters()) ||
            !empty($parameters->getIncludePaths());
    }

    /**
     * Get an Eloquent adapter for the supplied record's relationship.
     *
     * @param Relation $relation
     * @return AbstractAdapter
     */
    protected function adapterFor($relation)
    {
        $adapter = $this->getStore()->adapterFor($relation->getModel());

        if (!$adapter instanceof AbstractAdapter) {
            throw new RuntimeException('Expecting inverse resource adapter to be an Eloquent adapter.');
        }

        return $adapter;
    }

}
