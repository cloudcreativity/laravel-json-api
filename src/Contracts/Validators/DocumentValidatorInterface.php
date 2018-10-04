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

use CloudCreativity\LaravelJsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Utils\ErrorsAwareInterface;

/**
 * Interface DocumentValidatorInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0 use classes in the `Validation` namespace instead.
 */
interface DocumentValidatorInterface extends ErrorsAwareInterface
{

    /**
     * @param DocumentInterface $document
     * @param object|null $record
     *      the domain object that the document represents.
     * @return bool
     */
    public function isValid(DocumentInterface $document, $record = null);
}
