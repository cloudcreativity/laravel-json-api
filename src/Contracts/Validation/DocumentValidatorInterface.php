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

namespace CloudCreativity\LaravelJsonApi\Contracts\Validation;

use Neomerx\JsonApi\Schema\ErrorCollection;

/**
 * Interface DocumentValidatorInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface DocumentValidatorInterface
{

    /**
     * Does the document fail to meet the JSON API specification?
     *
     * @return bool
     */
    public function fails(): bool;

    /**
     * Get the document that is subject of validation.
     *
     * @return object
     */
    public function getDocument();

    /**
     * Get the JSON API error objects.
     *
     * @return ErrorCollection
     */
    public function getErrors(): ErrorCollection;

}
