<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use CloudCreativity\LaravelJsonApi\Utils\ErrorBag;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class Validator implements ValidatorInterface
{

    /**
     * @var ValidatorContract
     */
    private $validator;

    /**
     * Validator constructor.
     *
     * @param ValidatorContract $validator
     */
    public function __construct(ValidatorContract $validator)
    {
        $this->validator = $validator;
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
     * @inheritDoc
     */
    public function getErrors()
    {
        return $this->getErrorBag()->getErrors();
    }

    /**
     * @return ErrorBag
     */
    public function getErrorBag()
    {
        return ErrorBag::create($this->getMessageBag());
    }

}
