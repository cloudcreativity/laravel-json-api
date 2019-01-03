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

namespace CloudCreativity\LaravelJsonApi\Validators;

use CloudCreativity\LaravelJsonApi\Contracts\Object\DocumentInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\DocumentValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ResourceValidatorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Utils\ErrorsAwareTrait;
use CloudCreativity\LaravelJsonApi\Utils\Pointer as P;

/**
 * Class ResourceDocumentValidator
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0 use classes in the `Validation` namespace instead.
 */
class ResourceDocumentValidator implements DocumentValidatorInterface
{

    use ErrorsAwareTrait;

    /**
     * @var ValidatorErrorFactoryInterface
     */
    private $errorFactory;

    /**
     * @var ResourceValidatorInterface
     */
    private $resourceValidator;

    /**
     * ResourceDocumentValidator constructor.
     *
     * @param ValidatorErrorFactoryInterface $errorFactory
     * @param ResourceValidatorInterface $validator
     */
    public function __construct(
        ValidatorErrorFactoryInterface $errorFactory,
        ResourceValidatorInterface $validator
    ) {
        $this->errorFactory = $errorFactory;
        $this->resourceValidator = $validator;
    }

    /**
     * @inheritdoc
     */
    public function isValid(DocumentInterface $document, $record = null)
    {
        $this->reset();

        if (!$document->has(DocumentInterface::DATA)) {
            $this->addError($this->errorFactory->memberRequired(DocumentInterface::DATA, P::root()));
            return false;
        }

        $data = $document->get(DocumentInterface::DATA);

        if (!is_object($data)) {
            $this->addError($this->errorFactory->memberObjectExpected(DocumentInterface::DATA, P::data()));
            return false;
        }

        if (!$this->resourceValidator->isValid($document->getResource(), $record)) {
            $this->addErrors($this->resourceValidator->getErrors());
            return false;
        }

        return true;
    }
}
