<?php

namespace CloudCreativity\JsonApi\Validator\Attributes;

use CloudCreativity\JsonApi\Contracts\Error\ErrorObjectInterface;
use CloudCreativity\JsonApi\Validator\AbstractValidator;
use CloudCreativity\JsonApi\Validator\Helper\RequiredTrait;
use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Contracts\Validation\Validator;

/**
 * Class AttributesValidator
 * @package CloudCreativity\JsonApi
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
            ErrorObjectInterface::STATUS => 400,
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
    protected $_rules;

    /**
     * @var array
     */
    protected $_validationMessages;

    /**
     * The last Laravel validator that was used, or null if no validation has occurred.
     *
     * @var Validator|null
     */
    protected $_validator;

    /**
     * @param array $rules
     * @param array $validationMessages
     * @param bool $required
     */
    public function __construct(array $rules = [], array $validationMessages = [], $required = false)
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
        $this->_rules = $rules;

        return $this;
    }

    /**
     * @return array
     */
    public function getRules()
    {
        return (array) $this->_rules;
    }

    /**
     * @param array $messages
     * @return $this
     */
    public function setValidationMessages(array $messages)
    {
        $this->_validationMessages = $messages;

        return $this;
    }

    /**
     * @return array
     */
    public function getValidationMessages()
    {
        return (array) $this->_validationMessages;
    }

    /**
     * @return Validator|null
     */
    public function getValidator()
    {
        return $this->_validator;
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

        $validator = $this->make(get_object_vars($value));

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
        $this->_validator = \Validator::make($values, $this->getRules(), $this->getValidationMessages());

        return $this->_validator;
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
