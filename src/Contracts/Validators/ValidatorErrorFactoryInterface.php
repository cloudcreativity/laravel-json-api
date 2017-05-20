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

namespace CloudCreativity\LaravelJsonApi\Contracts\Validators;

use CloudCreativity\JsonApi\Contracts\Validators\ValidatorErrorFactoryInterface as BaseInterface;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

/**
 * Interface ValidatorErrorFactoryInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface ValidatorErrorFactoryInterface extends BaseInterface
{

    const STATUS_INVALID_ATTRIBUTES = Response::HTTP_UNPROCESSABLE_ENTITY;
    const STATUS_INVALID_PARAMETERS = Response::HTTP_BAD_REQUEST;

    /**
     * @param MessageBag $messageBag
     * @param string|null $attributePrefix
     *      a prefix if the validator relates to a nested attribute.
     * @param int $statusCode
     * @return ErrorInterface[]
     */
    public function resourceInvalidAttributesMessages(
        MessageBag $messageBag,
        $attributePrefix = null,
        $statusCode = self::STATUS_INVALID_ATTRIBUTES
    );

    /**
     * @param MessageBag $messages
     * @param string|null $prefix
     *      a prefix if the validator relates to a nested parameter.
     * @param int $statusCode
     * @return ErrorInterface[]
     */
    public function queryParametersMessages(
        MessageBag $messages,
        $prefix = null,
        $statusCode = self::STATUS_INVALID_PARAMETERS
    );
}
