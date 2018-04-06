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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Schema;

use CloudCreativity\LaravelJsonApi\Schema\ExtractsAttributesTrait;
use Neomerx\JsonApi\Factories\Factory;
use Neomerx\JsonApi\Schema\SchemaProvider;

/**
 * Class TestSchema
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class TestSchema extends SchemaProvider
{

    use ExtractsAttributesTrait;

    /**
     * @var
     */
    public $attributes;

    /**
     * @var bool
     */
    public $dasherize = true;

    /**
     * @var string|null
     */
    public $dateFormat;

    /**
     * @var string
     */
    protected $resourceType = 'test';

    /**
     * TestSchema constructor.
     */
    public function __construct()
    {
        parent::__construct(new Factory());
    }

    /**
     * @inheritDoc
     */
    public function getId($resource)
    {
        return $resource->id;
    }

    /**
     * @inheritDoc
     */
    protected function extractAttribute($record, $recordKey)
    {
        return $record->{$recordKey};
    }

    /**
     * @param $value
     * @param $record
     * @return string
     */
    protected function serializeFooAttribute($value, $record)
    {
        return strtoupper($value);
    }

}
