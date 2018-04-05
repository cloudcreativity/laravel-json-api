<?php

use CloudCreativity\JsonApi\Contracts\Document\MutableErrorInterface as Error;
use CloudCreativity\JsonApi\Validators\ValidatorErrorFactory as V;

return [

    /**
     * A compulsory member has not been included in document.
     */
    V::MEMBER_REQUIRED => [
        Error::TITLE => 'Required Member',
        Error::DETAIL => "The member '{member}' is required.",
        Error::STATUS => 400,
    ],

    /**
     * A non-object has been provided for a member that must be an object.
     */
    V::MEMBER_OBJECT_EXPECTED => [
        Error::TITLE => 'Object Expected',
        Error::DETAIL => "The member '{member}' must be an object.",
        Error::STATUS => 400,
    ],

    /**
     * A non-string has been provided for a member that must be a string.
     * E.g. the spec says that a resource's `type` and `id` keys MUST be strings.
     */
    V::MEMBER_STRING_EXPECTED => [
        Error::TITLE => 'String Expected',
        Error::DETAIL => "The member '{member}' must be a string.",
        Error::STATUS => 400,
    ],

    /**
     * A value was provided but it was empty, which is not allowed.
     */
    V::MEMBER_EMPTY_NOT_ALLOWED => [
        Error::TITLE => 'Value Expected',
        Error::DETAIL => "The member '{member}' cannot be empty.",
        Error::STATUS => 400,
    ],

    /**
     * A member that is expected to be a relationship is not an object, array or null value.
     */
    V::MEMBER_RELATIONSHIP_EXPECTED => [
        Error::TITLE => 'Relationship Expected',
        Error::DETAIL => "The member '{member}' must be a relationship object.",
        Error::STATUS => 400,
    ],

    /**
     * A resource's type is not supported.
     */
    V::RESOURCE_UNSUPPORTED_TYPE => [
        Error::TITLE => 'Unsupported Resource',
        Error::DETAIL => "Resource '{actual}' is not among the type(s) supported by this endpoint. Expecting only '{expected}' resources.",
        Error::STATUS => V::STATUS_UNSUPPORTED_TYPE,
    ],

    /**
     * A resource's id is not supported.
     */
    V::RESOURCE_UNSUPPORTED_ID => [
        Error::TITLE => 'Unsupported Resource',
        Error::DETAIL => "Resource id '{actual}' is not supported by this endpoint. Expecting only resource '{expected}'.",
        Error::STATUS => V::STATUS_UNSUPPORTED_ID,
    ],

    /**
     * Used when attributes are invalid but there are no validation error messages in the attributes validator.
     */
    V::RESOURCE_INVALID_ATTRIBUTES => [
        Error::TITLE => 'Invalid Attributes',
        Error::DETAIL => 'The attributes member is invalid.',
        Error::STATUS => 400,
    ],

    /**
     * Used when generating JSON API errors from validation messages (usually from a framework specific validator)
     * for the attributes member of a resource object.
     */
    V::RESOURCE_INVALID_ATTRIBUTES_MESSAGES => [
        Error::TITLE => 'Invalid Attribute',
        Error::STATUS => 422,
    ],

    /**
     * Used when relationships are invalid but there are no validation error messages in the relationships validator.
     */
    V::RESOURCE_INVALID_RELATIONSHIPS => [
        Error::TITLE => 'Invalid Relationships',
        Error::DETAIL => 'The relationships member is invalid.',
        Error::STATUS => 400,
    ],

    /**
     * Used when a has-one relationship is expected, but a has-many has been provided.
     */
    V::RELATIONSHIP_HAS_ONE_EXPECTED => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => 'The provided relationship must be a has-one relationship',
        Error::STATUS => 400,
    ],

    /**
     * Used when a has-many relationship is expected, but a has-one has been provided.
     */
    V::RELATIONSHIP_HAS_MANY_EXPECTED => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => 'The provided relationship must be a has-many relationship',
        Error::STATUS => 400,
    ],

    /**
     * When an empty relationship is not allowed.
     */
    V::RELATIONSHIP_EMPTY_NOT_ALLOWED => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => 'The provided relationship cannot be empty.',
        Error::STATUS => 422,
    ],

    /**
     * The related resource does not exist.
     */
    V::RELATIONSHIP_DOES_NOT_EXIST => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => 'The related resource does not exist.',
        Error::STATUS => V::STATUS_RELATED_RESOURCE_DOES_NOT_EXIST,
    ],

    /**
     * When a related resource is not logically acceptable for the relationship. This is the default error
     * used if an accept relationship callback/instance returns a boolean rather than an error or errors.
     */
    V::RELATIONSHIP_NOT_ACCEPTABLE => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => 'The related resource is not acceptable.',
        Error::STATUS => 422,
    ],

    /**
     * When the resource type of a related resource is not recognised.
     */
    V::RELATIONSHIP_UNKNOWN_TYPE => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => "Resource type '{actual}' is not recognised.",
        Error::STATUS => 400,
    ],

    /**
     * When a related resource is not of the correct type for the relationship.
     */
    V::RELATIONSHIP_UNSUPPORTED_TYPE => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => "Resource '{actual}' is not among the type(s) supported by this relationship. Expecting only '{expected}' resources.",
        Error::STATUS => 400,
    ],

    /**
     * Used when creating messages about the query parameters, usually from a framework specific validator.
     */
    V::QUERY_PARAMETERS_MESSAGES => [
        Error::TITLE => 'Invalid Query Parameter',
        Error::STATUS => 400,
    ],
];
