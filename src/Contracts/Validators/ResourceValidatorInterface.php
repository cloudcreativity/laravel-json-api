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

namespace CloudCreativity\LaravelJsonApi\Contracts\Validators;

use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Utils\ErrorsAwareInterface;

/**
 * Interface ResourceValidatorInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface ResourceValidatorInterface extends ErrorsAwareInterface
{

    /**
     * @param ResourceObjectInterface $resource
     * @param object|null $record
     *      the domain object that the resource represents.
     * @return bool
     */
    public function isValid(ResourceObjectInterface $resource, $record = null);
}
