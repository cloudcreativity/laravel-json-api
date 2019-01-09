<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

class Validator extends AbstractValidator
{

    /**
     * @inheritDoc
     */
    protected function createError(string $key, string $detail): ErrorInterface
    {
        return $this->errors->invalidResource($key, $detail);
    }

}
