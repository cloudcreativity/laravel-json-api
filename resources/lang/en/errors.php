<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default translatable members
    | of JSON API error objects. According to the JSON API spec, the
    | `title` and `detail` members can be localized. In addition the `code`
    | member is also read from this package if you want to give the error
    | a specific code.
    |
    | Set any value to an empty string if you do not want the member to be
    | included in the error object.
    |
    | @see http://jsonapi.org/format/#errors
    */

    'member_required' => [
        'title' => 'Required Member',
        'detail' => "The member ':member' is required.",
        'code' => '',
    ],

    'member_object_expected' => [
        'title' => 'Object Expected',
        'detail' => "The member ':member' must be an object.",
        'code' => '',
    ],

    'member_string_expected' => [
        'title' => 'String Expected',
        'detail' => "The member ':member' must be a string.",
        'code' => '',
    ],

    'member_empty' => [
        'title' => 'Value Expected',
        'detail' => "The member ':member' cannot be empty.",
        'code' => '',
    ],

    'resource_type_not_supported' => [
        'title' => 'Not Supported',
        'detail' => "Resource type ':type' is not supported by this endpoint.",
        'code' => '',
    ],

    'resource_type_not_recognised' => [
        'title' => 'Not Supported',
        'detail' => "Resource type ':type' is not recognised.",
        'code' => '',
    ],

    'resource_id_not_supported' => [
        'title' => 'Not Supported',
        'detail' => "Resource id ':id' is not supported by this endpoint.",
        'code' => '',
    ],

    'resource_exists' => [
        'title' => 'Conflict',
        'detail' => "Resource ':id' already exists.",
        'code' => '',
    ],

    'resource_not_found' => [
        'title' => 'Invalid Relationship',
        'detail' => "The related resource does not exist.",
        'code' => '',
    ],

    'resource_invalid' => [
        'title' => 'Unprocessable Entity',
        'detail' => 'The request was well-formed but contains semantic errors.',
        'code' => '',
    ],

    'query_invalid' => [
        'title' => 'Bad Request',
        'detail' => 'The request query parameters are invalid.',
        'code' => '',
    ],
];
