<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

use Neomerx\JsonApi\Contracts\Document\ErrorInterface;

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
    protected function createError($key, $detail): ErrorInterface
    {
        return $this->errors->invalidQueryParameter($key, $detail);
    }

}
