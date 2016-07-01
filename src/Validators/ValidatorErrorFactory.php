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

namespace CloudCreativity\LaravelJsonApi\Validators;

use CloudCreativity\JsonApi\Document\Error;
use CloudCreativity\JsonApi\Validators\ValidatorErrorFactory as BaseFactory;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Utils\ErrorBag;
use Illuminate\Contracts\Support\MessageBag;
use Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface;

/**
 * Class ValidatorErrorFactory
 * @package CloudCreativity\LaravelJsonApi
 */
class ValidatorErrorFactory extends BaseFactory implements ValidatorErrorFactoryInterface
{

    const RESOURCE_INVALID_ATTRIBUTES_MESSAGES = 'validation:resource-invalid-attributes-messages';
    const FILTER_PARAMETERS_MESSAGES = 'validation:filter-parameters-messages';

    /**
     * @inheritdoc
     */
    public function resourceInvalidAttributesMessages(
        MessageBag $messageBag,
        $attributePrefix = null,
        $statusCode = self::STATUS_INVALID_ATTRIBUTES
    ) {
        $prototype = $this->repository->error(self::RESOURCE_INVALID_ATTRIBUTES_MESSAGES);
        $prototype = Error::cast($prototype)->setStatus($statusCode);
        $prefix = $attributePrefix ? $this->getPathToAttribute($attributePrefix) : $this->getPathToAttributes();
        $errors = new ErrorBag($messageBag, $prototype, $prefix);

        return $errors->toArray();
    }

    /**
     * @inheritdoc
     */
    public function filterParametersMessages(MessageBag $messages, $statusCode = self::STATUS_INVALID_FILTERS)
    {
        $prototype = $this->repository->error(self::FILTER_PARAMETERS_MESSAGES);
        $prototype = Error::cast($prototype)->setStatus($statusCode);
        $prefix = QueryParametersParserInterface::PARAM_FILTER;
        $errors = new ErrorBag($messages, $prototype, $prefix, true);

        return $errors->toArray();
    }

}
