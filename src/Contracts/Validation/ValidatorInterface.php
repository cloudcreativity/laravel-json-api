<?php

namespace CloudCreativity\LaravelJsonApi\Contracts\Validation;

use Illuminate\Contracts\Validation\Validator;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * Interface ValidatorInterface
 *
 * @package CloudCreativity\LaravelJsonApi
 */
interface ValidatorInterface extends Validator
{

    /**
     * Get the JSON API error objects.
     *
     * @return ErrorCollection
     */
    public function getErrors(): ErrorCollection;
}
