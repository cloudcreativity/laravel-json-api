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

namespace CloudCreativity\JsonApi\Contracts\Validators;

use CloudCreativity\JsonApi\Contracts\Object\ResourceIdentifierInterface;
use Neomerx\JsonApi\Contracts\Document\ErrorInterface;
use Neomerx\JsonApi\Exceptions\ErrorCollection;

/**
 * Interface ValidatorErrorFactoryInterface
 *
 * @package CloudCreativity\JsonApi
 */
interface ValidatorErrorFactoryInterface
{

    const STATUS_UNSUPPORTED_TYPE = 409;
    const STATUS_UNSUPPORTED_ID = 409;
    const STATUS_RELATED_RESOURCE_DOES_NOT_EXIST = 404;

    /**
     * A compulsory member has not been included in the document.
     *
     * @param string $memberKey
     *      the name of the member that is missing.
     * @param string $pointer
     *      the pointer of where the member is expected in the document.
     * @return ErrorInterface
     */
    public function memberRequired($memberKey, $pointer);

    /**
     * A member is expected to be an object.
     *
     * @param string $memberKey
     *      the name of the member that is not an object.
     * @param string $pointer
     *      the pointer of where the member is in the document.
     * @return ErrorInterface
     */
    public function memberObjectExpected($memberKey, $pointer);

    /**
     * A member is expected to be a string.
     *
     * @param $memberKey
     * @param $pointer
     * @return ErrorInterface
     */
    public function memberStringExpected($memberKey, $pointer);

    /**
     * A member has an empty value, but a value is expected.
     *
     * @param $memberKey
     * @param $pointer
     * @return ErrorInterface
     */
    public function memberEmptyNotAllowed($memberKey, $pointer);

    /**
     * A member is expected to be a relationship - object, array or null.
     *
     * @param $memberKey
     * @param $pointer
     * @return ErrorInterface
     */
    public function memberRelationshipExpected($memberKey, $pointer);

    /**
     * The resource type is not supported.
     *
     * "A server MUST return 409 Conflict when processing a POST request in which the resource object's type is
     * not among the type(s) that constitute the collection represented by the endpoint."
     * http://jsonapi.org/format/#crud-creating
     *
     * "A server MUST return 409 Conflict when processing a PATCH request in which the resource object's type
     * and id do not match the server's endpoint."
     * http://jsonapi.org/format/#crud-updating
     *
     * @param string|string[] $expected
     *      the allowed resource type or types.
     * @param string $actual
     *      the actual resource type received.
     * @return ErrorInterface
     */
    public function resourceUnsupportedType($expected, $actual);

    /**
     * The resource id is not supported.
     *
     * "A server MUST return 409 Conflict when processing a PATCH request in which the resource object's type
     * and id do not match the server's endpoint."
     * http://jsonapi.org/format/#crud-updating
     *
     * @param string $expected
     * @param string $actual
     * @return ErrorInterface
     */
    public function resourceUnsupportedId($expected, $actual);

    /**
     * A generic error if attributes are invalid, but there are no other messages explaining why.
     *
     * @return ErrorInterface
     */
    public function resourceInvalidAttributes();

    /**
     * A generic error if relationships are invalid, but there are no other messages explaining why.
     *
     * @return ErrorInterface
     */
    public function resourceInvalidRelationships();

    /**
     * The related resource type is not known.
     *
     * @param string $actual
     *      the resource type that was provided.
     * @param string|null $relationshipKey
     *      the relationship key, or null if validating the relationship in the data member of a document.
     * @return ErrorInterface
     */
    public function relationshipUnknownType($actual, $relationshipKey = null);

    /**
     * The related resource is not of the correct type for the relationship.
     *
     * @param string|string[] $expected
     *      the allowed resource type or types.
     * @param $actual
     *      the actual resource type received.
     * @param string|null $relationshipKey
     *      the relationship key, or null if validating the relationship in the data member of a document.
     * @return ErrorInterface
     */
    public function relationshipUnsupportedType($expected, $actual, $relationshipKey = null);

    /**
     * A has-many relationship was provided for a has-one relationship.
     *
     * @param string|null $relationshipKey
     *      the relationship key, or null if validating the relationship in the data member of a document.
     * @return ErrorInterface
     */
    public function relationshipHasOneExpected($relationshipKey = null);

    /**
     * A has-one relationship was provided for a has-many relationship.
     *
     * @param string|null $relationshipKey
     *      the relationship key, or null if validating the relationship in the data member of a document.
     * @return ErrorInterface
     */
    public function relationshipHasManyExpected($relationshipKey = null);

    /**
     * An empty relationship was provided, but is not allowed.
     *
     * @param string|null $relationshipKey
     * @return ErrorInterface
     */
    public function relationshipEmptyNotAllowed($relationshipKey = null);

    /**
     * A request references a resource that does not exist.
     *
     * "A server MUST return 404 Not Found when processing a request that references a related resource that does
     * not exist."
     * http://jsonapi.org/format/#crud-updating-relationships
     *
     * @param ResourceIdentifierInterface $identifier
     *      the resource identifier that does not exist.
     * @param string|null $relationshipKey
     *      the relationship key, or null if validating the relationship in the data member of a document.
     * @return ErrorInterface
     */
    public function relationshipDoesNotExist(ResourceIdentifierInterface $identifier, $relationshipKey = null);

    /**
     * A resource is not logically acceptable for the relationship.
     *
     * @param ResourceIdentifierInterface $identifier
     *      the related resource that is not acceptable.
     * @param string|null $relationshipKey
     *      the relationship key, or null if validating the relationship in the data member of a document.
     * @param ErrorInterface|ErrorCollection|null
     *      a custom error message to use for this occurrence
     * @return ErrorCollection
     */
    public function relationshipNotAcceptable(
        ResourceIdentifierInterface $identifier,
        $relationshipKey = null,
        $errors = null
    );

    /**
     * @param mixed $messages
     *      validation messages
     * @param string|null $prefix
     *      a prefix if the validation messages relate to a nested attribute.
     * @param int $statusCode
     * @return ErrorCollection
     */
    public function resourceInvalidAttributesMessages($messages, $prefix = null, $statusCode = 422);

    /**
     * @param mixed $messages
     *      validation messages
     * @param string|null $prefix
     *      a prefix if the validation messages relate to a nested parameter.
     * @param int $statusCode
     * @return ErrorCollection
     */
    public function queryParametersMessages($messages, $prefix = null, $statusCode = 400);
}
