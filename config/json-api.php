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
    | Content Negotiation
    |--------------------------------------------------------------------------
    |
    | This is where you register how different media types are mapped to
    | encoders and decoders. Encoders do the work of converting your records
    | into JSON API resources. Decoders are used to convert incoming request
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
    | Schemas
    |--------------------------------------------------------------------------
    |
    | Schemas are the objects that convert a record object into its JSON API
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
    | column is not specified, the adapter will use `Model::getQualifiedKeyName()`
    | by default.
    */
    'eloquent-adapter' => [
        'map' => [
            'people' => 'App\Person',
        ],
        'columns' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Store Adapters
    |--------------------------------------------------------------------------
    |
    | Store adapters are used to locate your domain records using a JSON API
    | resource identifier. For Eloquent models you can configure the Eloquent
    | adapter above. For other records, you will need to write an adapter class
    | (implementing CloudCreativity\JsonApi\Contracts\Store\AdapterInterface).
    |
    | To attach these adapters to the store, list the fully qualified name of
    | your custom adapters here. These will be created via the service
    | container, so you can type-hint dependencies in an adapter's constructor.
    */
    'adapters' => [],

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Here you can customise how pagination is handled. If any values are
    | set as `null` then default values will be used.
    |
    | The `params` config sets the keys that the client uses for the page number
    | and the amount per-page in the request. The JSON API spec defines the
    | `page` parameter as where these will appear. So if the `params.page`
    | setting is `number`, the client will need to submit `page[number]=2` to
    | get the second page.
    |
    | The `meta` config sets the keys to use for pagination meta in responses.
    | Pagination meta will be added to your response meta under the key defined
    | in the `meta.key` setting. The other settings define the keys to use
    | within the pagination meta for the values returned by the Laravel
    | Paginator/LengthAwarePaginator contracts.
    |
    */
    'pagination' => [
        'params' => [
            'page' => null,
            'per-page' => null,
        ],
        'meta' => [
            'key' => null,
            'current-page' => null,
            'per-page' => null,
            'first-item' => null,
            'last-item' => null,
            'total' => null,
            'last-page' => null,
        ],
    ],
];
