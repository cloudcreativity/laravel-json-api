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
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class MorphHasMany
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class MorphHasMany implements HasManyAdapterInterface
{

    /**
     * @var HasManyAdapterInterface[]
     */
    private $adapters;

    /**
     * MorphToManyAdapter constructor.
     *
     * @param HasManyAdapterInterface[] ...$adapters
     */
    public function __construct(HasManyAdapterInterface ...$adapters)
    {
        $this->adapters = $adapters;
    }

    /**
     * Set the relationship name.
     *
     * @param $name
     * @return $this
     */
    public function withRelationshipName($name)
    {
        foreach ($this->adapters as $adapter) {
            if (method_exists($adapter, 'withRelationshipName')) {
                $adapter->withRelationshipName($name);
            }
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function query($record, EncodingParametersInterface $parameters)
    {
        $all = collect();

        foreach ($this->adapters as $adapter) {
            $all = $all->merge($adapter->query($record, $parameters));
        }

        return $all;
    }

    /**
     * @inheritDoc
     */
    public function relationship($record, EncodingParametersInterface $parameters)
    {
        $all = collect();

        foreach ($this->adapters as $adapter) {
            $all = $all->merge($adapter->relationship($record, $parameters));
        }

        return $all;
    }

    /**
     * @inheritdoc
     */
    public function update($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->update($record, $relationship, $parameters);
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function replace($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->replace($record, $relationship, $parameters);
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function add($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->add($record, $relationship, $parameters);
        }

        return $record;
    }

    /**
     * @inheritDoc
     */
    public function remove($record, RelationshipInterface $relationship, EncodingParametersInterface $parameters)
    {
        foreach ($this->adapters as $adapter) {
            $adapter->remove($record, $relationship, $parameters);
        }

        return $record;
    }

}
