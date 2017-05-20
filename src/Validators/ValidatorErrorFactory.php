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

namespace CloudCreativity\LaravelJsonApi\Validators;

use CloudCreativity\JsonApi\Document\Error;
use CloudCreativity\JsonApi\Exceptions\MutableErrorCollection;
use CloudCreativity\JsonApi\Utils\Pointer as P;
use CloudCreativity\JsonApi\Validators\ValidatorErrorFactory as BaseFactory;
use CloudCreativity\LaravelJsonApi\Utils\ErrorBag;
use Illuminate\Contracts\Support\MessageBag;

/**
 * Class ValidatorErrorFactory
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class ValidatorErrorFactory extends BaseFactory
{

    /**
     * @param MessageBag $messages
     * @param string|null $attributePrefix
     * @param int $statusCode
     * @return MutableErrorCollection
     */
    public function resourceInvalidAttributesMessages($messages, $attributePrefix = null, $statusCode = 422)
    {
        $prototype = $this->repository->error(self::RESOURCE_INVALID_ATTRIBUTES_MESSAGES);
        $prototype = Error::cast($prototype)->setStatus($statusCode);
        $prefix = $attributePrefix ? P::attribute($attributePrefix) : P::attributes();
        $errorBag = new ErrorBag($messages, $prototype, $prefix);

        return $errorBag->getErrors();
    }

    /**
     * @param MessageBag $messages
     * @param string|null $prefix
     * @param int $statusCode
     * @return MutableErrorCollection
     */
    public function queryParametersMessages($messages, $prefix = null, $statusCode = 400)
    {
        $prototype = $this->repository->error(self::QUERY_PARAMETERS_MESSAGES);
        $prototype = Error::cast($prototype)->setStatus($statusCode);
        $errorBag = new ErrorBag($messages, $prototype, $prefix, true);

        return $errorBag->getErrors();
    }

}
