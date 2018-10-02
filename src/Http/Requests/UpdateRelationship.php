<?php

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorProviderInterface;
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

        /** Pre-1.0 validators */
        if ($validators instanceof ValidatorProviderInterface) {
            $validators->relationshipQueryChecker()->checkQuery($this->getEncodingParameters());
            return;
        }

        /** 1.0 validators */
        $validators->modifyRelationshipQueryChecker($this->getQueryParameters())
            ->checkQuery($this->getEncodingParameters());
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
        $spec = $this->factory->createRelationshipDocumentValidator($document);

        if ($spec->fails()) {
            throw new ValidationException($spec->getErrors());
        }

        /** Check the document is logically correct. */
        if (!$validators = $this->getValidators()) {
            return;
        }

        /** Pre-1.0 validators */
        if ($validators instanceof ValidatorProviderInterface) {
            $this->validateDocumentWithProvider($validators, $document);
            return;
        }

        /** 1.0 validators */
        $validator = $validators->modifyRelationship(
            $this->getRecord(),
            $this->getRelationshipName(),
            $this->all()
        );

        if ($validator->fails()) {
            throw new ValidationException($validator->getErrors());
        }
    }

    /**
     * @param ValidatorProviderInterface $validators
     * @param $document
     * @deprecated 2.0.0
     */
    protected function validateDocumentWithProvider(ValidatorProviderInterface $validators, $document)
    {
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
