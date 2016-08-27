<?php

/**
 * Copyright 2016 Cloud Creativity Limited
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

use CloudCreativity\JsonApi\Contracts\Http\RequestInterpreterInterface;
use CloudCreativity\JsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorProviderInterface;
use CloudCreativity\JsonApi\Exceptions\ValidationException;
use Neomerx\JsonApi\Exceptions\JsonApiException;

/**
 * Class ChecksDocuments
 * @package CloudCreativity\LaravelJsonApi
 */
trait ChecksDocuments
{

    /**
     * @param ValidatorProviderInterface $validators
     * @param RequestInterpreterInterface $interpreter
     * @param JsonApiRequest $request
     * @throws JsonApiException
     */
    protected function checkDocumentIsAcceptable(
        ValidatorProviderInterface $validators,
        RequestInterpreterInterface $interpreter,
        JsonApiRequest $request
    ) {
        $document = $request->getDocument();

        if (!$document) {
            return;
        }

        $validator = $this->documentAcceptanceValidator($validators, $interpreter, $request);

        if ($validator && !$validator->isValid($document->getContent())) {
            throw new ValidationException($validator->getErrors());
        }
    }

    /**
     * @param ValidatorProviderInterface $validators
     * @param RequestInterpreterInterface $interpreter
     * @param JsonApiRequest $request
     * @return DocumentValidatorInterface|null
     */
    private function documentAcceptanceValidator(
        ValidatorProviderInterface $validators,
        RequestInterpreterInterface $interpreter,
        JsonApiRequest $request
    ) {
        $resourceType = $request->getResourceType();
        $resourceId = $interpreter->getResourceId();
        $relationshipName = $interpreter->getRelationshipName();
        $record = $request->getRecord();

        /** Create Resource */
        if ($interpreter->isCreateResource()) {
            return $validators->createResource($resourceType);
        } /** Update Resource */
        elseif ($interpreter->isUpdateResource()) {
            return $validators->updateResource($resourceType, $resourceId, $record);
        } /** Replace Relationship */
        elseif ($interpreter->isModifyRelationship()) {
            return $validators->modifyRelationship($resourceType, $resourceId, $relationshipName, $record);
        }

        return null;
    }

}
