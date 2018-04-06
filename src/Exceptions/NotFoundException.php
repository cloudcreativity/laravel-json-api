<?php

namespace CloudCreativity\LaravelJsonApi\Exceptions;

use CloudCreativity\LaravelJsonApi\Document\Error;
use Exception;
use Illuminate\Http\Response;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class NotFoundException extends JsonApiException
{

    /**
     * NotFoundException constructor.
     *
     * @param mixed $errors
     * @param Exception|null $previous
     */
    public function __construct($errors = [], Exception $previous = null)
    {
        parent::__construct($errors, Response::HTTP_NOT_FOUND, $previous);

        $this->addError(Error::create([
            Error::TITLE => 'Not Found',
            Error::STATUS => Response::HTTP_NOT_FOUND,
        ]));
    }
}