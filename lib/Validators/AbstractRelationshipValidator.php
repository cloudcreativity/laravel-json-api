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

use CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierCollectionInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\JsonApi\Contracts\Object\ResourceObjectInterface;
use CloudCreativity\JsonApi\Contracts\Store\StoreInterface;
use CloudCreativity\JsonApi\Contracts\Validators\AcceptRelatedResourceInterface;
use CloudCreativity\JsonApi\Contracts\Validators\RelationshipValidatorInterface;
use CloudCreativity\JsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\JsonApi\Utils\ErrorsAwareTrait;
use CloudCreativity\JsonApi\Utils\Pointer as P;

/**
 * Class AbstractRelationshipValidator
 *
 * @package CloudCreativity\JsonApi
 */
abstract class AbstractRelationshipValidator implements RelationshipValidatorInterface
{

    use ErrorsAwareTrait;

    /**
     * @var ValidatorErrorFactoryInterface
     */
    protected $errorFactory;

    /**
     * @var StoreInterface
     */
    private $store;

    /**
     * @var string[]|null
     *      if null, any types are supported.
     */
    private $expectedTypes;

    /**
     * @var bool
     */
    private $allowEmpty;

    /**
     * @var AcceptRelatedResourceInterface|null
     */
    private $acceptable;

    /**
     * HasOneValidator constructor.
     *
     * @param ValidatorErrorFactoryInterface $errorFactory
     * @param StoreInterface $store
     * @param string|string[]|null $expectedType
     * @param bool $allowEmpty
     * @param AcceptRelatedResourceInterface|null $acceptable
     */
    public function __construct(
        ValidatorErrorFactoryInterface $errorFactory,
        StoreInterface $store,
        $expectedType,
        $allowEmpty = false,
        AcceptRelatedResourceInterface $acceptable = null
    ) {
        $this->errorFactory = $errorFactory;
        $this->store = $store;
        $this->expectedTypes = !is_null($expectedType) ? (array) $expectedType : null;
        $this->allowEmpty = $allowEmpty;
        $this->acceptable = $acceptable;
    }

    /**
     * @return bool
     */
    protected function isEmptyAllowed()
    {
        return (bool) $this->allowEmpty;
    }

    /**
     * @param ResourceIdentifierInterface $identifier
     * @return bool
     */
    protected function doesExist(ResourceIdentifierInterface $identifier)
    {
        return $this->store->exists($identifier);
    }

    /**
     * @param $type
     * @return bool
     */
    protected function isKnownType($type)
    {
        return $this->store->isType($type);
    }

    /**
     * @param $type
     * @return bool
     */
    protected function isSupportedType($type)
    {
        if (!is_array($this->expectedTypes)) {
            return true;
        }

        return in_array($type, $this->expectedTypes, true);
    }

    /**
     * Validate that a data member exists and it is either a has-one or a has-many relationship.
     *
     * @param RelationshipInterface $relationship
     * @param string|null $key
     * @return bool
     */
    protected function validateRelationship(RelationshipInterface $relationship, $key = null)
    {
        if (!$relationship->has(RelationshipInterface::DATA)) {
            $this->addError($this->errorFactory->memberRequired(
                RelationshipInterface::DATA,
                $key ? P::relationship($key) : P::data()
            ));
            return false;
        }

        if (!$relationship->isHasOne() && !$relationship->isHasMany()) {
            $this->addError($this->errorFactory->memberRelationshipExpected(
                RelationshipInterface::DATA,
                $key ? P::relationship($key) : P::data()
            ));
            return false;
        }

        if (!$this->validateEmpty($relationship, $key)) {
            return false;
        }

        return true;
    }

    /**
     * Is this a valid has-one relationship?
     *
     * @param RelationshipInterface $relationship
     * @param null $record
     * @param null $key
     * @param ResourceObjectInterface|null $resource
     * @return bool
     */
    protected function validateHasOne(
        RelationshipInterface $relationship,
        $record = null,
        $key = null,
        ResourceObjectInterface $resource = null
    ) {
        if (!$relationship->isHasOne()) {
            $this->addError($this->errorFactory->relationshipHasOneExpected($key));
            return false;
        }

        $identifier = $relationship->getData();

        if (!$identifier) {
            return true;
        }

        /** Validate the identifier */
        if (!$this->validateIdentifier($identifier, $key)) {
            return false;
        }

        /** If an identifier has been provided, the resource it references must exist. */
        if (!$this->validateExists($identifier, $key)) {
            return false;
        }

        /** If an identifier has been provided, is it acceptable for the relationship? */
        if (!$this->validateAcceptable($identifier, $record, $key, $resource)) {
            return false;
        }

        return true;
    }

    /**
     * Is this a valid has-many relationship?
     *
     * @param RelationshipInterface $relationship
     * @param null $record
     * @param null $key
     * @param ResourceObjectInterface|null $resource
     * @return bool
     */
    protected function validateHasMany(
        RelationshipInterface $relationship,
        $record = null,
        $key = null,
        ResourceObjectInterface $resource = null
    ) {
        if (!$relationship->isHasMany()) {
            $this->addError($this->errorFactory->relationshipHasManyExpected($key));
            return false;
        }

        $identifiers = $relationship->getIdentifiers();

        if (!$this->validateIdentifiers($identifiers, $record, $key, $resource)) {
            return false;
        }

        return true;
    }

    /**
     * @param ResourceIdentifierInterface $identifier
     * @param string|null $key
     * @return bool
     */
    protected function validateIdentifier(ResourceIdentifierInterface $identifier, $key = null)
    {
        $valid = $this->validateIdentifierType($identifier, $key);

        if (!$this->validateIdentifierId($identifier, $key)) {
            return false;
        }

        return $valid;
    }

    /**
     * @param ResourceIdentifierInterface $identifier
     * @param string|null $key
     * @return bool
     */
    protected function validateIdentifierType(ResourceIdentifierInterface $identifier, $key = null)
    {
        /** Must have a type */
        if (!$identifier->hasType()) {
            $this->addError($this->errorFactory->memberRequired(
                $identifier::TYPE,
                $key ? P::relationshipData($key) : P::data()
            ));
            return false;
        }

        $type = $identifier->get($identifier::TYPE);

        /** Must be a string */
        if (!is_string($type)) {
            $this->addError($this->errorFactory->memberStringExpected(
                $identifier::TYPE,
                $key ? P::relationshipData($key) : P::data()
            ));
            return false;
        }

        /** String must not be empty */
        if (empty($type)) {
            $this->addError($this->errorFactory->memberEmptyNotAllowed(
                $identifier::TYPE,
                $key ? P::relationshipData($key) : P::data()
            ));
            return false;
        }

        /** Check the submitted resource type is a known resource type */
        if (!$this->isKnownType($type)) {
            $this->addError($this->errorFactory->relationshipUnknownType($type, $key));
            return false;
        }

        /** Check type is valid for this relationship */
        if (!$this->isSupportedType($type)) {
            $this->addError($this->errorFactory->relationshipUnsupportedType($this->expectedTypes, $type, $key));
            return false;
        }

        return true;
    }

    /**
     * @param ResourceIdentifierInterface $identifier
     * @param string|null $key
     * @return bool
     */
    protected function validateIdentifierId(ResourceIdentifierInterface $identifier, $key = null)
    {
        /** Must have an id */
        if (!$identifier->hasId()) {
            $this->addError($this->errorFactory->memberRequired(
                $identifier::ID,
                $key ? P::relationshipId($key) : P::data()
            ));
            return false;
        }

        $id = $identifier->get($identifier::ID);

        /** Id must be a string */
        if (!is_string($id)) {
            $this->addError($this->errorFactory->memberStringExpected(
               ResourceIdentifierInterface::ID,
               $key ? P::relationshipId($key) : P::data()
            ));
            return false;
        }

        /** Id must not be empty */
        if (empty($id)) {
            $this->addError($this->errorFactory->memberEmptyNotAllowed(
                ResourceIdentifierInterface::ID,
                $key ? P::relationshipId($key) : P::data()
            ));
            return false;
        }

        return true;
    }

    /**
     * @param ResourceIdentifierCollectionInterface $identifiers
     * @param object|null $record
     * @param string|null $key
     * @param ResourceObjectInterface $resource
     * @return bool
     */
    protected function validateIdentifiers(
        ResourceIdentifierCollectionInterface $identifiers,
        $record = null,
        $key = null,
        ResourceObjectInterface $resource = null
    ) {
        /** @var ResourceIdentifierInterface $identifier */
        foreach ($identifiers as $identifier) {

            if (!$this->validateIdentifier($identifier, $key) || !$this->validateExists($identifier, $key)) {
                return false;
            }
        }

        /** @var ResourceIdentifierInterface $identifier */
        foreach ($identifiers as $identifier) {

            if (!$this->validateAcceptable($identifier, $record, $key, $resource)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param ResourceIdentifierInterface $identifier
     * @param string|null
     * @return bool
     */
    protected function validateExists(ResourceIdentifierInterface $identifier, $key = null)
    {
        if (!$this->doesExist($identifier)) {
            $this->addError($this->errorFactory->relationshipDoesNotExist($identifier, $key));
            return false;
        }

        return true;
    }

    /**
     * @param ResourceIdentifierInterface $identifier
     * @param object|null
     * @param string|null $key
     * @param ResourceObjectInterface|null $resource
     * @return bool
     */
    protected function validateAcceptable(
        ResourceIdentifierInterface $identifier,
        $record = null,
        $key = null,
        ResourceObjectInterface $resource = null
    ) {
        $result = ($this->acceptable) ? $this->acceptable->accept($identifier, $record, $key, $resource) : true;

        if (true !== $result) {
            $this->addErrors($this->errorFactory->relationshipNotAcceptable(
                $identifier,
                $key,
                !is_bool($result) ? $result : null
            ));
            return false;
        }

        return true;
    }

    /**
     * @param RelationshipInterface $relationship
     * @param string|null $key
     * @return bool
     */
    private function validateEmpty(RelationshipInterface $relationship, $key = null)
    {
        if ($relationship->isHasOne()) {
            $empty = !$relationship->hasIdentifier();
        } else {
            $empty = $relationship->getIdentifiers()->isEmpty();
        }

        if ($empty && !$this->isEmptyAllowed()) {
            $this->addError($this->errorFactory->relationshipEmptyNotAllowed($key));
            return false;
        }

        return true;
    }

}
