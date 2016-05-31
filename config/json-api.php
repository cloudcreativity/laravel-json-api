<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JSON API Namespaces
    |--------------------------------------------------------------------------
    |
    | The aliases of the different APIs within your application that are
    | using the JSON API spec. For example, you may have a `v1` and `v2` API.
    | Generally these are going to match your route groups.
    |
    | Each namespace has the following configuration:
    |
    | - `url-prefix`: the URL prefix to be used when encoding resources.
    | - `supported-ext`: the supported extensions that apply to the whole
    | namespace.
    |
    */
    'namespaces' => [
        'v1' => [
            'url-prefix' => '/api/v1',
            'supported-ext' => null,
        ],
    ],

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
    | then an error will be sent to the client as per the JSON-API spec.
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
    | Schemas are the objects that convert a record object into its JSON-API
    | resource representation. This package supports having multiple sets of
    | schemas, using your JSON API namespaces as configured above. The
    | 'defaults' set is used for all namespaces, and the config for a
    | specific namespace is merged over the top of the 'defaults' set.
    |
    | Schema sets are a mapping of the record object class to the schema class
    | that is responsible for encoding it.
    */
    'schemas' => [
        'defaults' => [
            'App\Person' => 'App\JsonApi\Schemas\PeopleSchema',
        ],
        'v1' => [],
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
            'people' => 'App\Person',
        ],
        'columns' => [],
    ],
];
