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

namespace CloudCreativity\JsonApi\Validator\Attributes;

use CloudCreativity\JsonApi\Contracts\Error\ErrorObjectInterface;
use CloudCreativity\JsonApi\Object\ObjectUtils;
use CloudCreativity\JsonApi\Validator\AbstractValidator;
use CloudCreativity\JsonApi\Validator\Helper\RequiredTrait;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Validation\Validator;
use Validator as ValidatorFacade;

/**
 * Class AttributesValidator
 * @package CloudCreativity\JsonApi\Laravel
 */
class RulesValidator extends AbstractValidator
{

    const ERROR_INVALID_VALUE = 'invalid-value';
    const ERROR_VALIDATION_FAILED = 'invalid-attributes';
    const ERROR_INVALID_ATTRIBUTE = 'invalid-attribute';

    use RequiredTrait;

    /**
     * @var array
     */
    protected $templates = [
        self::ERROR_INVALID_VALUE => [
            ErrorObjectInterface::CODE => self::ERROR_INVALID_VALUE,
            ErrorObjectInterface::STATUS => 400,
            ErrorObjectInterface::TITLE => 'Invalid Value',
            ErrorObjectInterface::DETAIL => 'Attributes must be an object.',
        ],
        self::ERROR_VALIDATION_FAILED => [
            ErrorObjectInterface::CODE => self::ERROR_VALIDATION_FAILED,
            ErrorObjectInterface::STATUS => 422,
            ErrorObjectInterface::TITLE => 'Invalid Attributes',
            ErrorObjectInterface::DETAIL => 'The provided attributes are invalid.',
        ],
        self::ERROR_INVALID_ATTRIBUTE => [
            ErrorObjectInterface::CODE => self::ERROR_INVALID_ATTRIBUTE,
            ErrorObjectInterface::STATUS => 422,
            ErrorObjectInterface::TITLE => 'Invalid Attribute',
        ],
    ];

    /**
     * @var array
     */
    private $rules;

    /**
     * @var array
     */
    private $validationMessages;

    /**
     * The last Laravel validator that was used, or null if no validation has occurred.
     *
     * @var Validator|null
     */
    private $validator;

    /**
     * @param array $rules
     * @param array $validationMessages
     * @param bool $required
     */
    public function __construct(array $rules = [], array $validationMessages = [], $required = true)
    {
        $this->setRules($rules)
            ->setValidationMessages($validationMessages)
            ->setRequired($required);
    }

    /**
     * @param array $rules
     * @return $this
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;

        return $this;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return (array) $this->rules;
    }

    /**
     * @param array $messages
     * @return $this
     */
    public function setValidationMessages(array $messages)
    {
        $this->validationMessages = $messages;

        return $this;
    }

    /**
     * @return array
     */
    public function getValidationMessages()
    {
        return (array) $this->validationMessages;
    }

    /**
     * @return Validator|null
     */
    public function getValidator()
    {
        return $this->validator;
    }

    /**
     * @param $value
     */
    protected function validate($value)
    {
        if (!is_object($value)) {
            $this->error(static::ERROR_INVALID_VALUE);
            return;
        }

        $validator = $this->make(ObjectUtils::toArray($value));

        if ($validator->fails()) {
            $this->error(static::ERROR_VALIDATION_FAILED);
            $this->parseMessages($validator->getMessageBag());
        }
    }

    /**
     * @param array $values
     * @return Validator
     */
    protected function make(array $values)
    {
        $this->validator = ValidatorFacade::make($values, $this->getRules(), $this->getValidationMessages());

        return $this->validator;
    }

    /**
     * @param MessageBag $bag
     * @return $this
     */
    protected function parseMessages(MessageBag $bag)
    {
        foreach ($bag->toArray() as $key => $messages) {

            foreach ($messages as $message) {
                $this->error(static::ERROR_INVALID_ATTRIBUTE, '/' . $key)
                    ->setDetail($message);
            }
        }

        return $this;
    }

}
