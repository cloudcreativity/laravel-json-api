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
use Illuminate\Contracts\Support\MessageBag;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

class ValidatorErrorFactory extends BaseFactory implements ValidatorErrorFactoryInterface
{

    const RESOURCE_INVALID_ATTRIBUTES_MESSAGES = 'validation:resource-invalid-attributes-messages';

    /**
     * @param MessageBag $messageBag
     * @param int $statusCode
     * @return ErrorInterface[]
     */
    public function resourceInvalidAttributesMessages(
        MessageBag $messageBag,
        $statusCode = self::STATUS_INVALID_ATTRIBUTES
    ) {
        $errors = [];

        foreach ($messageBag->toArray() as $key => $messages) {
            $pointer = $this->getPathToAttribute($key);

            foreach ($messages as $detail) {
                $errors[] = $this->repository->errorWithPointer(
                    self::RESOURCE_INVALID_ATTRIBUTES_MESSAGES,
                    $pointer,
                    $statusCode,
                    [],
                    [Error::DETAIL => $detail]
                );
            }
        }

        return $errors;
    }

}
