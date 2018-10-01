<?php

namespace CloudCreativity\LaravelJsonApi\Validation\Document;

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
     * @var ErrorFactory
     */
    protected $errorFactory;

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
     * @param ErrorFactory
     * @param $document
     */
    public function __construct(ErrorFactory $factory, $document)
    {
        if (!is_object($document)) {
            throw new InvalidArgumentException('Expecting JSON API document to be an object.');
        }

        $this->document = $document;
        $this->errorFactory = $factory;
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

    /**
     * Add an error for a member that is required.
     *
     * @param string $path
     * @param string $member
     * @return void
     */
    protected function memberRequired($path, $member)
    {
        $this->errors->add($this->errorFactory->memberRequired($path, $member));
    }

    /**
     * Add an error for a member that must be an object.
     *
     * @param string $path
     * @param string $member
     * @return void
     */
    protected function memberNotObject($path, $member)
    {
        $this->errors->add($this->errorFactory->memberNotObject($path, $member));
    }

    /**
     * Add an error for a member that must be a string.
     *
     * @param string $path
     * @param string $member
     * @return void
     */
    protected function memberNotString($path, $member)
    {
        $this->errors->add($this->errorFactory->memberNotString($path, $member));
    }

    /**
     * Add an error for a member that cannot be an empty value.
     *
     * @param $path
     * @param $member
     * @return void
     */
    protected function memberEmpty($path, $member)
    {
        $this->errors->add($this->errorFactory->memberEmpty($path, $member));
    }

    /**
     * Add an error for when the resource type is not supported by the endpoint.
     *
     * @param $actual
     * @return void
     */
    protected function resourceTypeNotSupported($actual)
    {
        $this->errors->add($this->errorFactory->resourceTypeNotSupported($actual));
    }

    /**
     * Add an error for when the resource id is not supported by the endpoint.
     *
     * @param string $actual
     * @return void
     */
    protected function resourceIdNotSupported($actual)
    {
        $this->errors->add($this->errorFactory->resourceIdNotSupported($actual));
    }

    /**
     * Add an error for when a resource identifier does not exist.
     *
     * @param $path
     * @return void
     */
    protected function resourceDoesNotExist($path)
    {
        $this->errors->add($this->errorFactory->resourceDoesNotExist($path));
    }

}
