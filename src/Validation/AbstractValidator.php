<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * Class AbstractValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
abstract class AbstractValidator implements ValidatorInterface
{

    /**
     * @var ValidatorContract
     */
    protected $validator;

    /**
     * @var ErrorTranslator
     */
    protected $errors;

    /**
     * @param $key
     * @param $detail
     * @return ErrorInterface
     */
    abstract protected function createError($key, $detail);

    /**
     * Validator constructor.
     *
     * @param ValidatorContract $validator
     * @param ErrorTranslator $errors
     */
    public function __construct(ValidatorContract $validator, ErrorTranslator $errors)
    {
        $this->validator = $validator;
        $this->errors = $errors;
    }

    /**
     * @inheritDoc
     */
    public function validate()
    {
        return $this->validator->validate();
    }

    /**
     * @inheritDoc
     */
    public function fails()
    {
        return $this->validator->fails();
    }

    /**
     * @inheritDoc
     */
    public function failed()
    {
        return $this->validator->failed();
    }

    /**
     * @inheritDoc
     */
    public function sometimes($attribute, $rules, callable $callback)
    {
        return $this->validator->sometimes($attribute, $rules, $callback);
    }

    /**
     * @inheritDoc
     */
    public function after($callback)
    {
        return $this->validator->after($callback);
    }

    /**
     * @inheritDoc
     */
    public function errors()
    {
        return $this->validator->errors();
    }

    /**
     * @inheritDoc
     */
    public function getMessageBag()
    {
        return $this->validator->getMessageBag();
    }

    /**
     * @inheritdoc
     */
    public function getErrors()
    {
        $errors = new ErrorCollection();

        foreach ($this->getMessageBag()->toArray() as $key => $messages) {
            foreach ($messages as $detail) {
                $errors->add($this->createError($key, $detail));
            }
        }

        return $errors;
    }

}
