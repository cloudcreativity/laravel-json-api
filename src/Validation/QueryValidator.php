<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

/**
 * Class QueryValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class QueryValidator extends AbstractValidator
{

    /**
     * @inheritDoc
     */
    protected function createError($key, $detail)
    {
        return $this->errors->invalidQueryParameter($key, $detail);
    }

}
