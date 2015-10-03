<?php

/**
 * Copyright 2015 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Http\Controllers;

use CloudCreativity\JsonApi\Validator\Relationships\HasManyValidator;
use CloudCreativity\JsonApi\Validator\Relationships\HasOneValidator;
use CloudCreativity\JsonApi\Validator\Resource\IlluminateResourceValidator;

/**
 * Class DocumentValidatorTrait
 * @package CloudCreativity\JsonApi\Laravel
 */
trait DocumentValidatorTrait
{

    use DocumentDecoderTrait;

    /**
     * @see CloudCreativity\JsonApi\Validator\Resource\IlluminateResourceValidator
     * @param string $expectedType
     *      the resource type that is expected.
     * @param string|int|null $expectedId
     *      the resource id that is expected, or null if validating a new resource.
     * @param array $attributesValidationRules
     *      Laravel validation rules for the resource's attributes.
     * @param array $attributesValidationMessages
     *      Laravel validation messages for the resource's attributes.
     * @param bool $attributesMemberRequired
     *      Whether an attributes member is expected in the resource received from the client
     * @return IlluminateResourceValidator
     */
    public function getResourceObjectValidator(
        $expectedType,
        $expectedId = null,
        array $attributesValidationRules = [],
        array $attributesValidationMessages = [],
        $attributesMemberRequired = true
    ) {
        return new IlluminateResourceValidator(
            $expectedType,
            $expectedId,
            $attributesValidationRules,
            $attributesValidationMessages,
            $attributesMemberRequired
        );
    }

    /**
     * @param string|string[]|null $expectedTypeOrTypes
     * @return HasOneValidator
     */
    public function getHasOneValidator($expectedTypeOrTypes = null)
    {
        return new HasOneValidator($expectedTypeOrTypes);
    }

    /**
     * @param string|string[]|null $expectedTypeOrTypes
     * @return HasManyValidator
     */
    public function getHasManyValidator($expectedTypeOrTypes = null)
    {
        return new HasManyValidator($expectedTypeOrTypes);
    }
}
