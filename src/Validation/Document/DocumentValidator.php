<?php

namespace CloudCreativity\JsonApi\Validation\Document;

use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreAwareInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validation\Document\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Store\StoreAwareTrait;

class DocumentValidator extends AbstractValidator implements \IteratorAggregate
{

    use StoreAwareTrait;

    /**
     * @var DocumentValidatorInterface[]
     */
    private $stack;

    /**
     * DocumentValidator constructor.
     *
     * @param $document
     *      the JSON API document.
     * @param StoreInterface $store
     *      the store to use when validating the document.
     * @param DocumentValidatorInterface ...$stack
     */
    public function __construct($document, StoreInterface $store, DocumentValidatorInterface ...$stack)
    {
        parent::__construct($document);
        $this->store = $store;
        $this->stack = $stack;
    }

    /**
     * @inheritDoc
     */
    public function getIterator()
    {
        foreach ($this->stack as $validator) {
            if ($validator instanceof StoreAwareInterface) {
                $validator->withStore($this->getStore());
            }

            yield $validator;
        }
    }

    /**
     * @return bool
     */
    protected function validate()
    {
        $valid = true;

        /** @var DocumentValidatorInterface $validator */
        foreach ($this as $validator) {
            if ($validator->passes()) {
                continue;
            }

            $valid = false;

            foreach ($validator->getErrors() as $error) {
                $this->errors->add($error);
            }
        }

        return $valid;
    }
}
