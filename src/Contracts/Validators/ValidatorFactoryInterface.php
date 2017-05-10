<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

use CloudCreativity\JsonApi\Contracts\Validators\AttributesValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorFactoryInterface as BaseInterface;

/**
 * Interface ValidatorFactoryInterface
 * @package CloudCreativity\LaravelJsonApi
 */
interface ValidatorFactoryInterface extends BaseInterface
{

    /**
     * Get a resource attributes validator.
     *
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @param callable $callback
     *      a callback that will be called with the Laravel validator instance when it is made.
     * @param callable $extractor
     *      a callback to customise the extraction of attributes for validation.
     * @return AttributesValidatorInterface
     */
    public function attributes(
        array $rules,
        array $messages = [],
        array $customAttributes = [],
        callable $callback = null,
        callable $extractor = null
    );

    /**
     * Get a filter parameters validator.
     *
     * @param array $rules
     * @param array $messages
     * @param array $customAttributes
     * @param callable|null $callback
     * @return FilterValidatorInterface
     */
    public function filterParams(
        array $rules,
        array $messages = [],
        array $customAttributes = [],
        callable $callback = null
    );
}
