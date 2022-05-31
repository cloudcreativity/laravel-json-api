<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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

declare(strict_types=1);

namespace CloudCreativity\LaravelJsonApi\Schema;

use CloudCreativity\LaravelJsonApi\Contracts\ContainerInterface;
use Neomerx\JsonApi\Contracts\Factories\FactoryInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaContainerInterface;
use Neomerx\JsonApi\Contracts\Schema\SchemaInterface;

class SchemaContainer implements SchemaContainerInterface
{
    /**
     * @var ContainerInterface
     */
    private ContainerInterface $container;

    /**
     * @var FactoryInterface
     */
    private FactoryInterface $factory;

    /**
     * @var SchemaFields
     */
    private SchemaFields $fields;

    /**
     * SchemaContainer constructor.
     *
     * @param ContainerInterface $container
     * @param FactoryInterface $factory
     * @param SchemaFields|null $fields
     */
    public function __construct(ContainerInterface $container, FactoryInterface $factory, SchemaFields $fields = null)
    {
        $this->container = $container;
        $this->factory = $factory;
        $this->fields = $fields ?? new SchemaFields();
    }

    /**
     * @param SchemaFields $fields
     * @return void
     */
    public function setSchemaFields(SchemaFields $fields): void
    {
        $this->fields = $fields;
    }

    /**
     * @inheritDoc
     */
    public function getSchema($resourceObject): SchemaInterface
    {
        $schemaProvider = $this->container->getSchema($resourceObject);

        return new Schema(
            $this->factory,
            $schemaProvider,
            $this->fields,
        );
    }

    /**
     * @inheritDoc
     */
    public function hasSchema($resourceObject): bool
    {
        return \is_object($resourceObject) && $this->container->hasSchema($resourceObject);
    }
}