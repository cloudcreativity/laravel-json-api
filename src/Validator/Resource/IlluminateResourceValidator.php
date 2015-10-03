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

namespace CloudCreativity\JsonApi\Validator\Resource;

use CloudCreativity\JsonApi\Validator\Attributes\RulesValidator;
use CloudCreativity\JsonApi\Validator\Helper\RelationshipsValidatorTrait;
use CloudCreativity\JsonApi\Validator\Helper\RelationshipTrait;
use CloudCreativity\JsonApi\Validator\ResourceIdentifier\ExpectedIdValidator;
use CloudCreativity\JsonApi\Validator\ResourceIdentifier\ExpectedTypeValidator;

/**
 * Class IlluminateResourceValidator
 * @package CloudCreativity\JsonApi\Laravel
 */
class IlluminateResourceValidator extends AbstractResourceObjectValidator
{

    use RelationshipsValidatorTrait,
        RelationshipTrait;

    /**
     * @var string
     */
    private $expectedType;

    /**
     * @var string|int|null
     */
    private $expectedId;

    /**
     * @var RulesValidator
     */
    private $attributes;

    /**
     * @param string $expectedType
     *      the resource type that is expected.
     * @param string|int|null $expectedId
     *      the resource id that is expected, or null if validating a new resource.
     * @param array $attributesRules
     *      Laravel validation rules for the resource's attributes.
     * @param array $attributesValidationMessages
     *      Laravel validation messages for the resource's attributes.
     * @param bool $attributesRequired
     *      Whether an attributes member is expected in the resource received from the client
     */
    public function __construct(
        $expectedType,
        $expectedId = null,
        array $attributesRules = [],
        array $attributesValidationMessages = [],
        $attributesRequired = true
    ) {
        $this->expectedType = $expectedType;
        $this->expectedId = $expectedId;
        $this->attributes = new RulesValidator($attributesRules, $attributesValidationMessages, $attributesRequired);
    }

    /**
     * @return ExpectedTypeValidator
     */
    public function getTypeValidator()
    {
        return new ExpectedTypeValidator($this->expectedType);
    }

    /**
     * @return ExpectedIdValidator|null
     */
    public function getIdValidator()
    {
        return ($this->expectedId) ? new ExpectedIdValidator($this->expectedId) : null;
    }

    /**
     * @return RulesValidator
     */
    public function getAttributesValidator()
    {
        return $this->attributes;
    }

    /**
     * @return bool
     */
    public function isRequired()
    {
        return true;
    }
}
