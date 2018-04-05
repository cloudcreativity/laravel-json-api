<?php

namespace CloudCreativity\JsonApi\Exceptions;

use Exception;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * Class DocumentRequiredException
 *
 * Exception to indicate that a JSON API document (in an HTTP request body)
 * is expected but has not been provided.
 *
 * @package CloudCreativity\JsonApi
 */
class DocumentRequiredException extends JsonApiException
{

    /**
     * DocumentRequiredException constructor.
     *
     * @param $errors
     * @param Exception|null $previous
     */
    public function __construct($errors = [], Exception $previous = null)
    {
        parent::__construct($errors, self::HTTP_CODE_BAD_REQUEST, $previous);
    }
}
