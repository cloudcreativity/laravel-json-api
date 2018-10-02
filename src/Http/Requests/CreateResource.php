<?php

namespace CloudCreativity\LaravelJsonApi\Http\Requests;

use CloudCreativity\LaravelJsonApi\Exceptions\DocumentRequiredException;
use CloudCreativity\LaravelJsonApi\Exceptions\ValidationException;
use CloudCreativity\LaravelJsonApi\Object\Document;

/**
 * Class CreateResource
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class CreateResource extends ValidatedRequest
{

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!$authorizer = $this->getAuthorizer()) {
            return;
        }

        $authorizer->create($this->getResourceType(), $this->request);
    }

    /**
     * @inheritDoc
     */
    protected function validateQuery()
    {
        if (!$validators = $this->getValidators()) {
            return;
        }

        $validators->resourceQueryChecker()->checkQuery($this->getParameters());
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
        $spec = $this->factory->createResourceDocumentValidator($document, $this->getResourceType());

        if ($spec->fails()) {
            throw new ValidationException($spec->getErrors());
        }

        /** Check the document is logically correct. */
        if (!$validators = $this->getValidators()) {
            return;
        }

        $validator = $validators->createResource();

        if (!$validator->isValid(new Document($document))) {
            throw new ValidationException($validator->getErrors());
        }
    }

}
