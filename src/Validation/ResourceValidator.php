<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use Illuminate\Contracts\Validation\Validator as ValidatorContract;

class ResourceValidator extends AbstractValidator
{

    /**
     * @var ResourceObject
     */
    protected $resource;

    /**
     * ResourceValidator constructor.
     *
     * @param ValidatorContract $validator
     * @param ErrorTranslator $errors
     * @param ResourceObject $resource
     */
    public function __construct(
        ValidatorContract $validator,
        ErrorTranslator $errors,
        ResourceObject $resource
    ) {
        parent::__construct($validator, $errors);
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    protected function createError($key, $detail)
    {
        return $this->errors->invalidResource(
            $this->resource->pointer($key),
            $detail
        );
    }

}
