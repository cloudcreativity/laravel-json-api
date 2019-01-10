<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

use Illuminate\Contracts\Validation\Validator as ValidatorContract;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

class Validator extends AbstractValidator
{

    /**
     * @var \Closure
     */
    private $callback;

    /**
     * Validator constructor.
     *
     * @param ValidatorContract $validator
     * @param ErrorTranslator $errors
     * @param \Closure|null $callback
     */
    public function __construct(
        ValidatorContract $validator,
        ErrorTranslator $errors,
        \Closure $callback = null
    ) {
        parent::__construct($validator, $errors);
        $this->callback = $callback;
    }

    /**
     * @inheritDoc
     */
    protected function createError(string $key, string $detail): ErrorInterface
    {
        if ($fn = $this->callback) {
            return $fn($key, $detail, $this->errors);
        }

        return $this->errors->invalidResource($key, $detail);
    }

}
