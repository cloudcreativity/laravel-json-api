<?php
/**
 * Copyright 2018 Cloud Creativity Limited
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
    | - true: e.g. \DummyApp\JsonApi\Posts\{Adapter, Schema}
    | - false:
    |   - e.g. \DummyApp\JsonApi\Adapters\{User, Post, Comment}
    |   - e.g. \DummyApp\JsonApi\Schemas\{User, Post, Comment}
    |
    */
    'namespace' => 'DummyApp\JsonApi',
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
    | an Eloquent model `DummyApp\Post`, your mapping would be:
    |
    | `'posts' => DummyApp\Post::class`
    */
    'resources' => [
        'avatars' => \DummyApp\Avatar::class,
        'comments' => \DummyApp\Comment::class,
        'countries' => \DummyApp\Country::class,
        'downloads' => \DummyApp\Download::class,
        'phones' => \DummyApp\Phone::class,
        'posts' => \DummyApp\Post::class,
        'queue-jobs' => \CloudCreativity\LaravelJsonApi\Queue\ClientJob::class,
        'sites' => \DummyApp\Entities\Site::class,
        'tags' => \DummyApp\Tag::class,
        'users' => \DummyApp\User::class,
        'videos' => \DummyApp\Video::class,
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
    | Jobs
    |--------------------------------------------------------------------------
    |
    | Defines the class that is used to store client dispatched jobs. The
    | storing of these classes allows you to implement the JSON API
    | recommendation for asynchronous processing.
    |
    | We recommend referring to the Laravel JSON API documentation on
    | asynchronous processing if you are using this feature. If you use a
    | different class here, it must implement the asynchronous process
    | interface.
    |
    */
    'jobs' => \CloudCreativity\LaravelJsonApi\Queue\ClientJob::class,

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
    'providers' => [
        \DummyPackage\ResourceProvider::class,
    ],

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
