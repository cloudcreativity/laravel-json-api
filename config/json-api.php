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
    | Each API has the following configuration:
    |
    | ## url-prefix
    |
    | The URL prefix to be used when encoding responses. Use this if
    | your API is hosted within a URL namespace, e.g. `/api/v1`.
    |
    | ## supported-ext
    |
    | The supported extensions that apply to the whole API namespace.
    |
    | ## paging
    |
    | Sets the keys that the client uses for the page number and the amount
    | per-page in the request. The JSON API spec defines the `page` parameter
    | as where these will appear. So if the `paging.page` setting is `number`,
    | the client will need to submit `page[number]=2` to get the second page.
    | If either of the values are `null`, the default of `number` and `size`
    | is used.
    |
    | ## paging-meta
    |
    | This sets the keys to use for pagination meta in responses.
    | Pagination meta will be added to your response meta under the `page`
    | key. The settings define the keys to use within the pagination meta
    | for the values returned by the Laravel Paginator/LengthAwarePaginator
    | contracts. If any values are `null`, defaults will be used.
    |
    */
    'namespaces' => [
        'v1' => [
            'url-prefix' => '/api/v1',
            'supported-ext' => null,
            'paging' => [
                'page' => null,
                'per-page' => null,
            ],
            'paging-meta' => [
                'current-page' => null,
                'per-page' => null,
                'first-item' => null,
                'last-item' => null,
                'total' => null,
                'last-page' => null,
            ],
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
    | Generators
    |--------------------------------------------------------------------------
    |
    | This package supplies a set of handy generators. These make it possible
    | to easily generate every class needed to implement a JSON API resource.
    |
    | To smoothen out any rough edges, without enforcing any specific patterns
    | we have included a few handy configuration options, so that the generators
    | can follow your workflow.
    |
    | `namespace`       = The folder in which you will be storing everything
    |                     related to LaravelJsonApi.
    |                     (default: 'JsonApi')
    |
    | 'by_resource`     = Whether your JSON API resources relate to Eloquent models or not.
    |                     You can override the setting here when running a generator. If the
    |                     setting here is `true` running a generator with `--no-eloquent` will
    |                     override it; if the setting is `false`, then `--eloquent` is the override.
    |                     Choose:
    |                      - true (default)
    |                           e.g. \App\JsonApi\Tasks\{Schema, Request, Hydrator}
    |                      - false
    |                           e.g. \App\JsonApi\Schemas\{User, Post, Comment}
    |                           e.g. \App\JsonApi\Requests\{User, Post, Comment}
    |
    | `use_eloquent`    = Whether you are using Eloquent ORM in this app.
    |                     (default: true)
    */
    'generator' => [
        'namespace' => 'JsonApi',
        'by-resource' => true,
        'use-eloquent' => true,
    ],
];
