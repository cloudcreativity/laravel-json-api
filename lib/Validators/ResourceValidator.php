<?php

/**
 * Copyright 2017 Cloud Creativity Limited
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

namespace CloudCreativity\JsonApi\Validators;

use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Contracts\Validators\AttributesValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ResourceValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\JsonApi\Utils\ErrorsAwareTrait;
use CloudCreativity\JsonApi\Utils\Pointer as P;

/**
 * Class ResourceValidator
 *
 * @package CloudCreativity\JsonApi
 */
class ResourceValidator implements ResourceValidatorInterface
{

    use ErrorsAwareTrait;

    /**
     * @var ValidatorErrorFactoryInterface
     */
    private $errorFactory;

    /**
     * @var string|null
     *      null to accept any type
     */
    private $expectedType;

    /**
     * @var string|null
     */
    private $expectedId;

    /**
     * @var AttributesValidatorInterface|null
     */
    private $attributes;

    /**
     * @var RelationshipsValidatorInterface|null
     */
    private $relationships;

    /**
     * @var ResourceValidatorInterface|null
     */
    private $context;

    /**
     * ResourceValidator constructor.
     *
     * @param ValidatorErrorFactoryInterface $errorFactory
     * @param string|null $expectedType
     * @param string|int|null $expectedId
     * @param AttributesValidatorInterface|null $attributes
     * @param RelationshipsValidatorInterface|null $relationships
     * @param ResourceValidatorInterface|null $context
     */
    public function __construct(
        ValidatorErrorFactoryInterface $errorFactory,
        $expectedType = null,
        $expectedId = null,
        AttributesValidatorInterface $attributes = null,
        RelationshipsValidatorInterface $relationships = null,
        ResourceValidatorInterface $context = null
    ) {
        $this->errorFactory = $errorFactory;
        $this->expectedType = $expectedType;
        $this->expectedId = $expectedId ? (string) $expectedId : null;
        $this->attributes = $attributes;
        $this->relationships = $relationships;
        $this->context = $context;
    }

    /**
     * @inheritdoc
     */
    public function isValid(ResourceObjectInterface $resource, $record = null)
    {
        $this->reset();

        $valid = $this->validateType($resource);

        if (!$this->validateId($resource)) {
            $valid = false;
        }

        if (!$this->validateAttributes($resource, $record)) {
            $valid = false;
        }

        if (!$this->validateRelationships($resource, $record)) {
            $valid = false;
        }

        if ($valid && !$this->validateContext($resource, $record)) {
            $valid = false;
        }

        return $valid;
    }

    /**
     * @param $type
     * @return bool
     */
    protected function isSupportedType($type)
    {
        if (is_null($this->expectedType)) {
            return true;
        }

        return $this->expectedType === $type;
    }

    /**
     * @param ResourceObjectInterface $resource
     * @return bool
     */
    protected function validateType(ResourceObjectInterface $resource)
    {
        /** Type is required */
        if (!$resource->has(ResourceObjectInterface::TYPE)) {
            $this->addError($this->errorFactory->memberRequired(ResourceObjectInterface::TYPE, P::data()));
            return false;
        }

        $type = $resource->get(ResourceObjectInterface::TYPE);

        /** Type must be string */
        if (!is_string($type)) {
            $this->addError($this->errorFactory->memberStringExpected(
                $resource::TYPE,
                P::dataType()
            ));
            return false;
        }

        /** Type must not be empty */
        if (empty($type)) {
            $this->addError($this->errorFactory->memberEmptyNotAllowed(
                $resource::TYPE,
                P::dataType()
            ));
            return false;
        }

        /** Must be the expected type */
        if (!$this->isSupportedType($type)) {
            $this->addError($this->errorFactory->resourceUnsupportedType($this->expectedType, $type));
            return false;
        }

        return true;
    }

    /**
     * @param ResourceObjectInterface $resource
     * @return bool
     */
    protected function validateId(ResourceObjectInterface $resource)
    {
        /** If we are not expecting an id, and one has not been provided, we can return true. */
        if (!$this->isExpectingId() && !$resource->has($resource::ID)) {
            return true;
        }

        /** If expecting an id, one must be provided */
        if (!$resource->has(ResourceObjectInterface::ID)) {
            $this->addError($this->errorFactory->memberRequired(ResourceObjectInterface::ID, P::data()));
            return false;
        }

        $id = $resource->get($resource::ID);

        /** Id must be a string */
        if (!is_string($id)) {
            $this->addError($this->errorFactory->memberStringExpected(
                $resource::ID,
                P::dataId()
            ));
            return false;
        }

        /** Id must not be empty */
        if (empty($id)) {
            $this->addError($this->errorFactory->memberEmptyNotAllowed(
               $resource::ID,
               P::dataId()
            ));
            return false;
        }

        /** If expecting an id, must match the one we're expecting */
        if ($this->isExpectingId() && $this->expectedId != $resource->getId()) {
            $this->addError($this->errorFactory->resourceUnsupportedId(
                $this->expectedId,
                $resource->getId()
            ));
            return false;
        }

        return true;
    }

    /**
     * @param ResourceObjectInterface $resource
     * @param object|null $record
     * @return bool
     */
    protected function validateAttributes(ResourceObjectInterface $resource, $record = null)
    {
        $raw = $resource->get(ResourceObjectInterface::ATTRIBUTES);

        /** Attributes member must be an object. */
        if ($resource->has(ResourceObjectInterface::ATTRIBUTES) && !is_object($raw)) {
            $this->addError($this->errorFactory->memberObjectExpected(
                ResourceObjectInterface::ATTRIBUTES,
                P::attributes()
            ));
            return false;
        }

        /** Ok if no attributes validator or one that returns true for `isValid()` */
        if (!$this->attributes || $this->attributes->isValid($resource, $record)) {
            return true;
        }

        /** Ensure that at least one error message is added. */
        if (0 < count($this->attributes->getErrors())) {
            $this->addErrors($this->attributes->getErrors());
        } else {
            $this->addError($this->errorFactory->resourceInvalidAttributes());
        }

        return false;
    }

    /**
     * @param ResourceObjectInterface $resource
     * @param object|null $record
     * @return bool
     */
    protected function validateRelationships(ResourceObjectInterface $resource, $record = null)
    {
        $raw = $resource->get(ResourceObjectInterface::RELATIONSHIPS);

        /** Relationships member must be an object. */
        if ($resource->has(ResourceObjectInterface::RELATIONSHIPS) && !is_object($raw)) {
            $this->addError($this->errorFactory->memberObjectExpected(
                ResourceObjectInterface::RELATIONSHIPS,
                P::relationships()
            ));
            return false;
        }

        /** Ok if no relationships validator or one that returns true for `isValid()` */
        if (!$this->relationships || $this->relationships->isValid($resource, $record)) {
            return true;
        }

        /** Ensure there is at least one error message. */
        if (0 < count($this->relationships->getErrors())) {
            $this->addErrors($this->relationships->getErrors());
        } else {
            $this->addError($this->errorFactory->resourceInvalidRelationships());
        }

        return false;
    }

    /**
     * @param ResourceObjectInterface $resource
     * @param object|null $record
     * @return bool
     */
    protected function validateContext(ResourceObjectInterface $resource, $record = null)
    {
        if (!$this->context || $this->context->isValid($resource, $record)) {
            return true;
        }

        $this->addErrors($this->context->getErrors());

        return false;
    }

    /**
     * @return bool
     */
    protected function isExpectingId()
    {
        return !is_null($this->expectedId);
    }

}
