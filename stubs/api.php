<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Root Namespace
    |--------------------------------------------------------------------------
    |
    | The root namespace for JSON API classes for this API. If `null`, the
    | namespace will default to `JsonApi` within your application's root
    | namespace (obtained via Laravel's `Application::getNamespace()`
    | method).
    |
    | The `by-resource` setting determines how your units are organised within
    | your root namespace.
    |
    | - true: e.g. \App\JsonApi\Tasks\{Schema, Hydrator}
    | - false:
    |   - e.g. \App\JsonApi\Schemas\{User, Post, Comment}
    |   - e.g. \App\JsonApi\Hydrators\{User, Post, Comment}
    |
    */
    'namespace' => null,
    'by-resource' => true,

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | Here you map the list of JSON API resources in your API to the actual
    | record (model/entity) classes they relate to.
    |
    | For example, if you had a `posts` JSON API resource, that related to
    | an Eloquent model `App\Post`, your mapping would be:
    |
    | `'posts' => App\Post::class`
    */
    'resources' => [
        'posts' => 'App\Post',
    ],

    /*
    |--------------------------------------------------------------------------
    | Eloquent
    |--------------------------------------------------------------------------
    |
    | Whether your JSON API resources predominantly relate to Eloquent models.
    | This is used by the package's generators.
    |
    | You can override the setting here when running a generator. If the
    | setting here is `true` running a generator with `--no-eloquent` will
    | override it; if the setting is `false`, then `--eloquent` is the override.
    |
    */
    'use-eloquent' => true,

    /*
    |--------------------------------------------------------------------------
    | URL Prefix
    |--------------------------------------------------------------------------
    |
    | The URL prefix to be used when encoding responses. Use this if
    | your API is hosted within a URL namespace, e.g. `/api/v1`.
    |
    | Although we could detect this for HTTP requests, we need it defined here
    | for when we are encoding outside of HTTP requests, e.g. broadcasting.
    |
    */
    'url-prefix' => '/api/v1',

    /*
    |--------------------------------------------------------------------------
    | Supported JSON API Extensions
    |--------------------------------------------------------------------------
    |
    | Refer to the JSON API spec for information on supported extensions.
    |
    */
    'supported-ext' => null,

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
    'codecs' => [
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
    | Providers
    |--------------------------------------------------------------------------
    |
    | Providers allow vendor packages to include resources in your API. E.g.
    | a Shopping Cart vendor package might define the `orders` and `payments`
    | JSON API resources.
    |
    | A package author will define a provider class in their package that you
    | can add here. E.g. for our shopping cart example, the provider could be
    | `Vendor\ShoppingCart\JsonApi\ResourceProvider`.
    |
    */
    'providers' => [],

    /*
    |--------------------------------------------------------------------------
    | Errors
    |--------------------------------------------------------------------------
    |
    | This is an array of JSON API errors that can be returned by the API.
    | The value here is an array of errors specific to this API. These will
    | be merged on top of the default errors supplied by this package (merging
    | is not recursive) and stored in your `json-api-errors` config file.
    */
    'errors' => [],

];
