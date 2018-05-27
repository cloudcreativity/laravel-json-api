<?php

/**
 * Copyright 2018 Cloud Creativity Limited
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

namespace CloudCreativity\LaravelJsonApi\Validators;

use CloudCreativity\LaravelJsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\RelationshipValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Utils\ErrorsAwareTrait;

/**
 * Class RelationshipDocumentValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class RelationshipDocumentValidator implements DocumentValidatorInterface
{

    use ErrorsAwareTrait;

    /**
     * @var ValidatorErrorFactoryInterface
     */
    private $errorFactory;

    /**
     * @var RelationshipValidatorInterface
     */
    private $relationshipValidator;

    /**
     * RelationshipDocumentValidator constructor.
     *
     * @param ValidatorErrorFactoryInterface $errorFactory
     * @param RelationshipValidatorInterface $validator
     */
    public function __construct(
        ValidatorErrorFactoryInterface $errorFactory,
        RelationshipValidatorInterface $validator
    ) {
        $this->errorFactory = $errorFactory;
        $this->relationshipValidator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function isValid(DocumentInterface $document, $record = null)
    {
        $this->reset();

        if (!$this->relationshipValidator->isValid($document->getRelationship(), $record)) {
            $this->addErrors($this->relationshipValidator->getErrors());
            return false;
        }

        return true;
    }

}
