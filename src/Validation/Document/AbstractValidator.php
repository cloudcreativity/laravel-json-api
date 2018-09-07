<?php

namespace CloudCreativity\JsonApi\Validation\Document;

use CloudCreativity\LaravelJsonApi\Contracts\Validation\Document\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Exceptions\InvalidArgumentException;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

abstract class AbstractValidator implements DocumentValidatorInterface
{

    /**
     * @var object
     */
    protected $document;

    /**
     * @var ErrorCollection
     */
    protected $errors;

    /**
     * @var bool|null
     */
    private $valid;

    /**
     * @return bool
     */
    abstract protected function validate();

    /**
     * AbstractValidator constructor.
     *
     * @param $document
     */
    public function __construct($document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Expecting JSON API document to be an object.');
        }

        $this->document = $document;
        $this->errors = new ErrorCollection();
    }

    /**
     * @inheritDoc
     */
    public function fails()
    {
        return !$this->passes();
    }

    /**
     * @inheritDoc
     */
    public function passes()
    {
        if (is_bool($this->valid)) {
            return $this->valid;
        }

        return $this->valid = $this->validate();
    }

    /**
     * @inheritDoc
     */
    public function getDocument()
    {
        return $this->document;
    }

    /**
     * @inheritDoc
     */
    public function getErrors()
    {
        return clone $this->errors;
    }

}
