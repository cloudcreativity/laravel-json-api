<?php

/**
 * Copyright 2020 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Tests\Unit\Validators;

use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ResourceValidatorInterface;
use CloudCreativity\LaravelJsonApi\Utils\ErrorsAwareTrait;

/**
 * Class TestContextValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class TestContextValidator implements ResourceValidatorInterface
{

    use ErrorsAwareTrait;

    /**
     * @var callable
     */
    private $callback;

    /**
     * TestContextValidator constructor.
     *
     * @param callable $callback
     */
    public function __construct(callable $callback)
    {
        $this->callback = $callback;
    }

    /**
     * @inheritdoc
     */
    public function isValid(ResourceObjectInterface $resource, $record = null)
    {
        $callback = $this->callback;

        return (bool) $callback($resource, $record, $this);
    }

}
