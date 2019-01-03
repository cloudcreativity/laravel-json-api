<?php
/**
 * Copyright 2019 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
        $this->passes(
            $validators->modifyRelationshipQuery($this->query())
        );
    }

    /**
     * @inheritDoc
     */
    protected function validateDocument()
    {
        if (!$document = $this->decode()) {
            throw new DocumentRequiredException();
        }

        $validators = $this->getValidators();

        /** Pre-1.0 validators */
        if ($validators instanceof ValidatorProviderInterface) {
            $this->validateDocumentWithProvider($validators, $document);
            return;
        }

        /** Check the document is compliant with the JSON API spec. */
        $this->passes(
            $this->factory->createRelationshipDocumentValidator($document)
        );

        /** Check the document is logically correct. */
        if ($validators) {
            $this->passes($validators->modifyRelationship(
                $this->getRecord(),
                $this->getRelationshipName(),
                $this->all()
            ));
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

        if (!$validator->isValid(new Document($document), $this->getRecord())) {
            throw new ValidationException($validator->getErrors());
        }
    }

}
