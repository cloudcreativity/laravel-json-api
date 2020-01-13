<?php

/**
 * Copyright 2020 Cloud Creativity Limited
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

use CloudCreativity\LaravelJsonApi\Contracts\Document\MutableErrorInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceIdentifierInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Repositories\ErrorRepositoryInterface;
use CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface;
use CloudCreativity\LaravelJsonApi\Document\Error;
use CloudCreativity\LaravelJsonApi\Exceptions\MutableErrorCollection;
use CloudCreativity\LaravelJsonApi\Utils\ErrorBag;
use CloudCreativity\LaravelJsonApi\Utils\Pointer as P;
use Illuminate\Contracts\Support\MessageBag;

/**
 * Class ValidatorErrorFactory
 *
 * @package CloudCreativity\LaravelJsonApi
 * @deprecated 2.0.0 use classes in the `Validation` namespace instead.
 */
class ValidatorErrorFactory implements ValidatorErrorFactoryInterface
{

    const MEMBER_REQUIRED = 'validation:member-required';
    const MEMBER_OBJECT_EXPECTED = 'validation:member-object-expected';
    const MEMBER_STRING_EXPECTED = 'validation:member-string-expected';
    const MEMBER_EMPTY_NOT_ALLOWED = 'validation:member-empty-not-allowed';
    const MEMBER_RELATIONSHIP_EXPECTED = 'validation:member-relationship-expected';
    const RESOURCE_UNSUPPORTED_TYPE = 'validation:resource-unsupported-type';
    const RESOURCE_UNSUPPORTED_ID = 'validation:resource-unsupported-id';
    const RESOURCE_INVALID_ATTRIBUTES = 'validation:resource-invalid-attributes';
    const RESOURCE_INVALID_ATTRIBUTES_MESSAGES = 'validation:resource-invalid-attributes-messages';
    const RESOURCE_INVALID_RELATIONSHIPS = 'validation:resource-invalid-relationships';
    const RELATIONSHIP_UNKNOWN_TYPE = 'validation:relationship-unknown-type';
    const RELATIONSHIP_UNSUPPORTED_TYPE = 'validation:relationship-unsupported-type';
    const RELATIONSHIP_HAS_ONE_EXPECTED = 'validation:relationship-has-one-expected';
    const RELATIONSHIP_HAS_MANY_EXPECTED = 'validation:relationship-has-many-expected';
    const RELATIONSHIP_EMPTY_NOT_ALLOWED = 'validation:relationship-empty-not-allowed';
    const RELATIONSHIP_DOES_NOT_EXIST = 'validation:relationship-does-not-exist';
    const RELATIONSHIP_NOT_ACCEPTABLE = 'validation:relationship-not-acceptable';
    const QUERY_PARAMETERS_MESSAGES = 'validation:query-parameters-messages';

    /**
     * @var ErrorRepositoryInterface
     */
    protected $repository;

    /**
     * ValidatorErrorFactory constructor.
     *
     * @param ErrorRepositoryInterface $repository
     */
    public function __construct(ErrorRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @inheritdoc
     */
    public function memberRequired($memberKey, $pointer)
    {
        return $this->repository->errorWithPointer(self::MEMBER_REQUIRED, $pointer, [
            'member' => $memberKey,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function memberObjectExpected($memberKey, $pointer)
    {
        return $this->repository->errorWithPointer(self::MEMBER_OBJECT_EXPECTED, $pointer, [
            'member' => $memberKey,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function memberStringExpected($memberKey, $pointer)
    {
        return $this->repository->errorWithPointer(self::MEMBER_STRING_EXPECTED, $pointer, [
            'member' => $memberKey,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function memberEmptyNotAllowed($memberKey, $pointer)
    {
        return $this->repository->errorWithPointer(self::MEMBER_EMPTY_NOT_ALLOWED, $pointer, [
            'member' => $memberKey,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function memberRelationshipExpected($memberKey, $pointer)
    {
        return $this->repository->errorWithPointer(self::MEMBER_RELATIONSHIP_EXPECTED, $pointer, [
            'member' => $memberKey,
        ]);
    }

    /**
     * @inheritdoc
     */
    public function resourceUnsupportedType($expected, $actual)
    {
        $error = $this->repository->errorWithPointer(
            self::RESOURCE_UNSUPPORTED_TYPE,
            P::type(),
            ['expected' => $expected, 'actual' => $actual]
        );

        $error->setStatus(self::STATUS_UNSUPPORTED_TYPE);

        return $error;
    }

    /**
     * @inheritdoc
     */
    public function resourceUnsupportedId($expected, $actual)
    {
        $error = $this->repository->errorWithPointer(
            self::RESOURCE_UNSUPPORTED_ID,
            P::id(),
            ['expected' => $expected, 'actual' => $actual]
        );

        $error->setStatus(self::STATUS_UNSUPPORTED_ID);

        return $error;
    }

    /**
     * @inheritdoc
     */
    public function resourceInvalidAttributes()
    {
        return $this->repository->errorWithPointer(
            self::RESOURCE_INVALID_ATTRIBUTES,
            P::attributes()
        );
    }

    /**
     * @inheritdoc
     */
    public function resourceInvalidRelationships()
    {
        return $this->repository->errorWithPointer(
            self::RESOURCE_INVALID_RELATIONSHIPS,
            P::relationships()
        );
    }

    /**
     * @inheritdoc
     */
    public function relationshipUnknownType($actual, $relationshipKey = null)
    {
        return $this->repository->errorWithPointer(
            self::RELATIONSHIP_UNKNOWN_TYPE,
            $relationshipKey ? P::relationshipType($relationshipKey) : P::type(),
            ['actual' => $actual]
        );
    }

    /**
     * @inheritdoc
     */
    public function relationshipUnsupportedType($expected, $actual, $relationshipKey = null)
    {
        return $this->repository->errorWithPointer(
            self::RELATIONSHIP_UNSUPPORTED_TYPE,
            $relationshipKey ? P::relationshipType($relationshipKey) : P::type(),
            ['expected' => $expected, 'actual' => $actual]
        );
    }

    /**
     * @inheritdoc
     */
    public function relationshipHasOneExpected($relationshipKey = null)
    {
        return $this->repository->errorWithPointer(
            self::RELATIONSHIP_HAS_ONE_EXPECTED,
            $relationshipKey ? P::relationship($relationshipKey) : P::data()
        );
    }

    /**
     * @inheritdoc
     */
    public function relationshipHasManyExpected($relationshipKey = null)
    {
        return $this->repository->errorWithPointer(
            self::RELATIONSHIP_HAS_MANY_EXPECTED,
            $relationshipKey ? P::relationship($relationshipKey) : P::data()
        );
    }

    /**
     * @inheritdoc
     */
    public function relationshipEmptyNotAllowed($relationshipKey = null)
    {
        return $this->repository->errorWithPointer(
            self::RELATIONSHIP_EMPTY_NOT_ALLOWED,
            $relationshipKey ? P::relationship($relationshipKey) : P::data()
        );
    }

    /**
     * @inheritdoc
     */
    public function relationshipDoesNotExist(ResourceIdentifierInterface $identifier, $relationshipKey = null)
    {
        $error = $this->repository->errorWithPointer(
            self::RELATIONSHIP_DOES_NOT_EXIST,
            $relationshipKey ? P::relationship($relationshipKey) : P::data(),
            ['type' => $identifier->getType(), 'id' => $identifier->getId()]
        );

        $error->setStatus(self::STATUS_RELATED_RESOURCE_DOES_NOT_EXIST);

        return $error;
    }

    /**
     * @inheritdoc
     */
    public function relationshipNotAcceptable(
        ResourceIdentifierInterface $identifier,
        $relationshipKey = null,
        $error = null
    ) {
        $base = $this->repository->errorWithPointer(
            self::RELATIONSHIP_NOT_ACCEPTABLE,
            $relationshipKey ? P::relationship($relationshipKey) : P::data(),
            ['type' => $identifier->getType(), 'id' => $identifier->getId()]
        );

        $errors = new MutableErrorCollection();

        /** @var MutableErrorInterface $err */
        foreach (MutableErrorCollection::cast($error ?: $base) as $err) {
            $add = clone $base;
            $errors->add($add->merge($err));
        }

        return $errors;
    }

    /**
     * @param MessageBag $messages
     * @param string|null $attributePrefix
     * @param int $statusCode
     * @return MutableErrorCollection
     */
    public function resourceInvalidAttributesMessages($messages, $attributePrefix = null, $statusCode = 422)
    {
        $prototype = $this->repository->error(self::RESOURCE_INVALID_ATTRIBUTES_MESSAGES);
        $prototype = Error::cast($prototype)->setStatus($statusCode);
        $prefix = $attributePrefix ? P::attribute($attributePrefix) : P::attributes();
        $errorBag = new ErrorBag($messages, $prototype, $prefix);

        return $errorBag->getErrors();
    }

    /**
     * @param MessageBag $messages
     * @param string|null $prefix
     * @param int $statusCode
     * @return MutableErrorCollection
     */
    public function queryParametersMessages($messages, $prefix = null, $statusCode = 400)
    {
        $prototype = $this->repository->error(self::QUERY_PARAMETERS_MESSAGES);
        $prototype = Error::cast($prototype)->setStatus($statusCode);
        $errorBag = new ErrorBag($messages, $prototype, $prefix, true);

        return $errorBag->getErrors();
    }

}
