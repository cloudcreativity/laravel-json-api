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
    | - true: e.g. \App\JsonApi\Posts\{Schema, Hydrator}
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
    | URL
    |--------------------------------------------------------------------------
    |
    | The API's url, made up of a host, URL namespace and route name prefix.
    |
    | If a JSON API is handling an inbound request, the host will always be
    | detected from the inbound HTTP request. In other circumstances
    | (e.g. broadcasting), the host will be taken from the setting here.
    | If it is `null`, the `app.url` config setting is used as the default.
    | If you set `host` to `false`, the host will never be appended to URLs
    | for inbound requests.
    |
    | The name setting is the prefix for route names within this API.
    |
    */
    'url' => [
        'host' => null,
        'namespace' => '/api/v1',
        'name' => 'api:v1:',
    ],

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
    | The value here is an array of errors specific to this API, with string
    | array keys that are the references used to create those errors.
    |
    | Errors contained here will be merged on top of the default errors
    | supplied by this package (merging is not recursive). This means if you
    | need to override any of the default errors, you can include an error here
    | with the same key as the default error you want to override. Default
    | errors can be found in the package's 'config/json-api-errors.php' file.
    */
    'errors' => [],

];
