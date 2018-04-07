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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Adapter;

use CloudCreativity\LaravelJsonApi\Adapter\AbstractResourceAdapter;
use CloudCreativity\LaravelJsonApi\Adapter\HydratesAttributesTrait;
use CloudCreativity\LaravelJsonApi\Contracts\Object\RelationshipsInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

/**
 * Class TestAdapter
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class TestAdapter extends AbstractResourceAdapter
{

    use HydratesAttributesTrait;

    /**
     * The attributes that can be hydrated
     *
     * @var array|null
     */
    public $attributes;

    /**
     * Attributes to cast as dates
     *
     * @var array|null
     */
    public $dates;

    /**
     * @inheritDoc
     */
    public function query(EncodingParametersInterface $parameters)
    {
        // TODO: Implement query() method.
    }

    /**
     * @inheritDoc
     */
    public function read($resourceId, EncodingParametersInterface $parameters)
    {
        // TODO: Implement read() method.
    }

    /**
     * @inheritDoc
     */
    public function delete($record, EncodingParametersInterface $parameters)
    {
        $record->destroyed = true;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function exists($resourceId)
    {
        // TODO: Implement exists() method.
    }

    /**
     * @inheritDoc
     */
    public function find($resourceId)
    {
        // TODO: Implement find() method.
    }

    /**
     * @inheritDoc
     */
    public function findMany(array $resourceIds)
    {
        // TODO: Implement findMany() method.
    }

    /**
     * @inheritDoc
     */
    public function related($relationshipName)
    {
        // TODO: Implement related() method.
    }

    /**
     * @inheritDoc
     */
    protected function createRecord(ResourceObjectInterface $resource)
    {
        $id = $resource->getId();

        return (object) compact('id');
    }

    /**
     * @inheritDoc
     */
    protected function hydrateRelationships(
        $record,
        RelationshipsInterface $relationships,
        EncodingParametersInterface $parameters
    ) {
        // TODO: Implement hydrateRelationships() method.
    }

    /**
     * @inheritDoc
     */
    protected function hydrateAttribute($record, $attrKey, $value)
    {
        $record->{$attrKey} = $value;
    }

    /**
     * @param $record
     * @param $value
     */
    protected function hydrateTitleField($record, $value)
    {
        $record->title = ucwords($value);
    }

    /**
     * @inheritDoc
     */
    protected function persist($record)
    {
        if (!isset($record->id)) {
            $record->id = 'new';
        }

        $record->saved = true;

        return $record;
    }

}
