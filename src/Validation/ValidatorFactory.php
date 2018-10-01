<?php

namespace CloudCreativity\LaravelJsonApi\Validation;

use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\DocumentValidatorInterface;

class ValidatorFactory
{

    /**
     * @var StoreInterface
     */
    private $store;

    /**
     * @var ErrorFactory
     */
    private $errors;

    /**
     * ValidatorFactory constructor.
     *
     * @param StoreInterface $store
     * @param ErrorFactory $errors
     */
    public function __construct(StoreInterface $store, ErrorFactory $errors)
    {
        $this->store = $store;
        $this->errors = $errors;
    }

    /**
     * Create a validator to check that a resource document complies with the JSON API specification.
     *
     * @param object $document
     * @param string $expectedType
     *      the expected resource type.
     * @param string|null $expectedId
     *      the expected resource id if updating an existing resource.
     * @return DocumentValidatorInterface
     */
    public function resourceDocument($document, $expectedType, $expectedId = null)
    {
        return new Spec\ResourceValidator(
            $this->store,
            $this->errors,
            $document,
            $expectedType,
            $expectedId
        );
    }

    /**
     * Create a validator to check that a relationship document complies with the JSON API specification.
     *
     * @param object $document
     * @return DocumentValidatorInterface
     */
    public function relationshipDocument($document)
    {
        return new Spec\RelationValidator($this->store, $this->errors, $document);
    }
}
