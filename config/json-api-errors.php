<?php

use CloudCreativity\LaravelJsonApi\Contracts\Document\MutableErrorInterface as Error;
use CloudCreativity\LaravelJsonApi\Validators\ValidatorErrorFactory as V;
use CloudCreativity\LaravelJsonApi\Http\Headers\RestrictiveHeadersChecker as H;
use Illuminate\Http\Response;

return [

    /**
     * The client's `Accept` header does not a configured encoder.
     */
    H::NOT_ACCEPTABLE => [
        Error::TITLE => 'Not Acceptable',
        Error::STATUS => Response::HTTP_NOT_ACCEPTABLE,
    ],

    /**
     * The client's `Content-Type` header contains multiple media types, so we do not
     * know which media type to match against.
     */
    H::MULTIPLE_MEDIA_TYPES => [
        Error::TITLE => 'Invalid Content-Type Header',
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * The client's `Content-Type` header does not match a configured decoder.
     */
    H::UNSUPPORTED_MEDIA_TYPE => [
        Error::TITLE => 'Invalid Content-Type Header',
        Error::STATUS => Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
        Error::DETAIL => 'The specified content type is not supported.',
    ],

    /**
     * A compulsory member has not been included in document.
     */
    V::MEMBER_REQUIRED => [
        Error::TITLE => 'Required Member',
        Error::DETAIL => "The member '{member}' is required.",
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * A non-object has been provided for a member that must be an object.
     */
    V::MEMBER_OBJECT_EXPECTED => [
        Error::TITLE => 'Object Expected',
        Error::DETAIL => "The member '{member}' must be an object.",
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * A non-string has been provided for a member that must be a string.
     * E.g. the spec says that a resource's `type` and `id` keys MUST be strings.
     */
    V::MEMBER_STRING_EXPECTED => [
        Error::TITLE => 'String Expected',
        Error::DETAIL => "The member '{member}' must be a string.",
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * A value was provided but it was empty, which is not allowed.
     */
    V::MEMBER_EMPTY_NOT_ALLOWED => [
        Error::TITLE => 'Value Expected',
        Error::DETAIL => "The member '{member}' cannot be empty.",
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * A member that is expected to be a relationship is not an object, array or null value.
     */
    V::MEMBER_RELATIONSHIP_EXPECTED => [
        Error::TITLE => 'Relationship Expected',
        Error::DETAIL => "The member '{member}' must be a relationship object.",
        Error::STATUS => Response::HTTP_BAD_REQUEST,
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
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * Used when validating attributes with a Laravel validator. The source pointer and error detail will
     * be set using the message bag that the Laravel validator returns, and a 422 response status will be
     * set.
     */
    V::RESOURCE_INVALID_ATTRIBUTES_MESSAGES => [
        Error::TITLE => 'Invalid Attribute',
        Error::STATUS => Response::HTTP_UNPROCESSABLE_ENTITY,
    ],

    /**
     * Used when relationships are invalid but there are no validation error messages in the relationships validator.
     */
    V::RESOURCE_INVALID_RELATIONSHIPS => [
        Error::TITLE => 'Invalid Relationships',
        Error::DETAIL => 'The relationships member is invalid.',
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * Used when a has-one relationship is expected, but a has-many has been provided.
     */
    V::RELATIONSHIP_HAS_ONE_EXPECTED => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => 'The provided relationship must be a has-one relationship',
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * Used when a has-many relationship is expected, but a has-one has been provided.
     */
    V::RELATIONSHIP_HAS_MANY_EXPECTED => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => 'The provided relationship must be a has-many relationship',
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * When an empty relationship is not allowed.
     */
    V::RELATIONSHIP_EMPTY_NOT_ALLOWED => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => 'The provided relationship cannot be empty.',
        Error::STATUS => Response::HTTP_UNPROCESSABLE_ENTITY,
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
     * When a related resource is not logically acceptable for the relationship.
     */
    V::RELATIONSHIP_NOT_ACCEPTABLE => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => 'The related resource is not acceptable.',
        Error::STATUS => Response::HTTP_UNPROCESSABLE_ENTITY,
    ],

    /**
     * When the resource type of a related resource is not recognised.
     */
    V::RELATIONSHIP_UNKNOWN_TYPE => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => "Resource type '{actual}' is not recognised.",
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * When a related resource is not of the correct type for the relationship.
     */
    V::RELATIONSHIP_UNSUPPORTED_TYPE => [
        Error::TITLE => 'Invalid Relationship',
        Error::DETAIL => "Resource '{actual}' is not among the type(s) supported by this relationship. Expecting only '{expected}' resources.",
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * Used when validating the query parameter with a Laravel validator. The source
     * parameter and error detail will be set using the message bag that the Laravel validator returns,
     * and a 400 response status will be set.
     */
    V::QUERY_PARAMETERS_MESSAGES => [
        Error::TITLE => 'Invalid Query Parameter',
        Error::STATUS => Response::HTTP_BAD_REQUEST,
    ],

    /**
     * Exceptions
     *
     * To register errors for specific exceptions, use the fully qualified exception class as the
     * key. The default exception parser will use the error below for the generic `Exception` class if
     * there is no error registered for an exception class that it is parsing.
     */
    Exception::class => [
        Error::TITLE => 'Internal Server Error',
        Error::STATUS => Response::HTTP_INTERNAL_SERVER_ERROR,
    ],
];
