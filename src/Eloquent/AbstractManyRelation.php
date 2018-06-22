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

use CloudCreativity\LaravelJsonApi\Contracts\Adapter\HasManyAdapterInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\RuntimeException;
use Illuminate\Database\Eloquent\Model;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class AbstractManyRelation
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractManyRelation extends AbstractRelation implements HasManyAdapterInterface
{

    /**
     * @param Model $record
     * @param EncodingParametersInterface $parameters
     * @return mixed
     */
    public function query($record, EncodingParametersInterface $parameters)
    {
        /** If we do not need to pass to the inverse adapter, we can just return the whole relationship. */
        if (!$this->requiresInverseAdapter($record, $parameters)) {
            return $record->{$this->key};
        }

        $relation = $this->getRelation($record);
        $adapter = $this->getStore()->adapterFor($relation->getModel());

        if (!$adapter instanceof AbstractAdapter) {
            throw new RuntimeException('Expecting inverse adapter to be an Eloquent adapter.');
        }

        return $adapter->queryRelation($relation, $parameters);
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

}
