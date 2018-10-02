<?php

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Exceptions\ValidationException;
use CloudCreativity\LaravelJsonApi\Object\Document;

class UpdateRelationship extends ValidatedRequest
{

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!$authorizer = $this->getAuthorizer()) {
            return;
        }

        $authorizer->modifyRelationship(
            $this->getRecord(),
            $this->getRelationshipName(),
            $this->request
        );
    }

    /**
     * @inheritDoc
     */
    protected function validateQuery()
    {
        if (!$validators = $this->getInverseValidators()) {
            return;
        }

        $validators->relationshipQueryChecker()->checkQuery($this->getParameters());
    }

    /**
     * @inheritDoc
     */
    protected function validateDocument()
    {
        if (!$document = $this->decode()) {
            throw new DocumentRequiredException();
        }

        /** Check the document is compliant with the JSON API spec. */
        $spec = $this->factory->relationshipDocument($document);

        if ($spec->fails()) {
            throw new ValidationException($spec->getErrors());
        }

        /** Check the document is logically correct. */
        if (!$validators = $this->getValidators()) {
            return;
        }

        $validator = $validators->modifyRelationship(
            $this->getResourceId(),
            $this->getRelationshipName(),
            $this->getRecord()
        );

        if (!$validator->isValid(new Document($document))) {
            throw new ValidationException($validator->getErrors());
        }
    }

}
