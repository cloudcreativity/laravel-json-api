<?php

namespace CloudCreativity\LaravelJsonApi\Validation\Spec;

class RelationValidator extends AbstractValidator
{

    /**
     * @inheritDoc
     */
    protected function validate(): bool
    {
        return $this->validateRelationship($this->document);
    }

}
