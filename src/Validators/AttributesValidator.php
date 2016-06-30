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

use CloudCreativity\JsonApi\Contracts\Object\ResourceInterface;
use CloudCreativity\JsonApi\Contracts\Validators\AttributesValidatorInterface;
use Illuminate\Contracts\Validation\Validator;

/**
 * Class AttributesValidator
 * @package CloudCreativity\LaravelJsonApi
 */
class AttributesValidator extends AbstractValidator implements AttributesValidatorInterface
{

    /**
     * Are the attributes on the supplied resource valid?
     *
     * @param ResourceInterface $resource
     * @return bool
     */
    public function isValid(ResourceInterface $resource)
    {
        $validator = $this->make($resource->attributes()->toArray());

        if ($validator->fails()) {
            $this->addValidatorErrors($validator);
            return false;
        }

        return true;
    }

    /**
     * @param Validator $validator
     */
    protected function addValidatorErrors(Validator $validator)
    {
        $messages = $validator->getMessageBag();
        $this->addErrors($this->errorFactory->resourceInvalidAttributesMessages($messages));
    }

}
