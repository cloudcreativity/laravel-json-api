<?php

namespace CloudCreativity\LaravelJsonApi\Contracts\Validation\Document;

use Neomerx\JsonApi\Exceptions\ErrorCollection;

interface DocumentValidatorInterface
{

    /**
     * Does the document fail to meet the JSON API specification?
     *
     * @return bool
     */
    public function fails();

    /**
     * Does the document meet the JSON API specification?
     *
     * @return bool
     */
    public function passes();

    /**
     * Get the document that is subject of validation.
     *
     * @return object
     */
    public function getDocument();

    /**
     * Get the JSON API errors objects.
     *
     * @return ErrorCollection
     */
    public function getErrors();
}
