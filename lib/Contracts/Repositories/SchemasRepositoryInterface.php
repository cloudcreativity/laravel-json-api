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

namespace CloudCreativity\JsonApi\Contracts\Repositories;

use CloudCreativity\JsonApi\Contracts\Utils\ConfigurableInterface;
use Neomerx\JsonApi\Contracts\Schema\ContainerInterface;

/**
 * Interface SchemaRepositoryInterface
 *
 * @package CloudCreativity\JsonApi
 * @deprecated
 */
interface SchemasRepositoryInterface extends ConfigurableInterface
{

    const DEFAULTS = 'defaults';

    /**
     * @param string|null $schemas
     *      The name of the schema set, or empty to get the default schemas.
     * @return ContainerInterface
     */
    public function getSchemas($schemas = null);
}
