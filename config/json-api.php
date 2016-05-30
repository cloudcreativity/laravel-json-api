<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Codec Matcher Configuration
    |--------------------------------------------------------------------------
    |
    | This is where you register how different media types are mapped to
    | encoders and decoders. Encoders do the work of converting your records
    | into Json-Api resources. Decoders are used to convert incoming request
    | body content into objects.
    |
    | If there is not an encoder/decoder registered for a specific media-type,
    | then an error will be sent to the client as per the Json-Api spec.
    |
    */
    'codec-matcher' => [
        'encoders' => [
            'application/vnd.api+json',
            'text/plain' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
        ],
        'decoders' => [
            'application/vnd.api+json',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Sets
    |--------------------------------------------------------------------------
    |
    | Schemas are the objects that convert a record object into its Json-Api
    | resource representation. This package supports having multiple sets of
    | schemas, which is useful if you have different api end-points in your
    | application (e.g. you might have 'v1' and 'v2' endpoints).
    |
    | Schema sets are a mapping of the record object class to the schema class
    | that is responsible for encoding it. The 'default' set is used if no
    | specific set is a middleware parameter. If using additional sets, the
    | named additional set will be merged with the default set.
    */
    'schemas' => [
        'defaults' => [
            'ModelClass' => 'SchemaClass',
        ],
        'v1' => [],
        'v2' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Eloquent Adapter
    |--------------------------------------------------------------------------
    |
    | The Eloquent adapter is used to look up whether a record exists for a
    | resource type and id, and for retrieving that record. The adapter takes
    | two configuration arrays:
    |
    | `map` - a map of JSON API resource types to Eloquent Model classes.
    |
    | `columns` (optional) - a map of JSON API resource types to the column
    | name that is used for the resource id. These are optional - if a
    | column is not provided for a Model class, the adapter will use
    | `Model::getQualifiedKeyName()` by default.
    */
    'eloquent-adapter' => [
        'map' => [
            'resource-type' => 'ModelClass',
        ],
        'columns' => [],
    ],
];
