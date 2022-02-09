<?php
/*
 * Copyright 2022 Cloud Creativity Limited
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
    | Model Namespace
    |--------------------------------------------------------------------------
    |
    | Here you can decide where your api models live.
    |
    | By default Models live on the root of the application like App\Post hence the model-namespace: 'App'
    | but you can set it any other place like App\Models\Post hence the model-namespace: 'App\Models'
    |
    */
    'model-namespace' => null,

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
        'histories' => \DummyApp\History::class,
        'images' => \DummyApp\Image::class,
        'phones' => \DummyApp\Phone::class,
        'posts' => \DummyApp\Post::class,
        'roles' => \DummyApp\Role::class,
        'sites' => \DummyApp\Entities\Site::class,
        'suppliers' => \DummyApp\Supplier::class,
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
    | Defines settings for the asynchronous processing feature. We recommend
    | referring to the documentation on asynchronous processing if you are
    | using this feature.
    |
    | Note that if you use a different model class, it must implement the
    | asynchronous process interface.
    |
    */
    'jobs' => [
        'resource' => 'queue-jobs',
        'model' => \CloudCreativity\LaravelJsonApi\Queue\ClientJob::class,
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
    | Encoding Media Types
    |--------------------------------------------------------------------------
    |
    | This defines the JSON API encoding used for particular media
    | types supported by your API. This array can contain either
    | media types as values, or can be keyed by a media type with the value
    | being the options that are passed to the `json_encode` method.
    |
    | These values are also used for Content Negotiation. If a client requests
    | via the HTTP Accept header a media type that is not listed here,
    | a 406 Not Acceptable response will be sent.
    |
    | If you want to support media types that do not return responses with JSON
    | API encoded data, you can do this at runtime. Refer to the
    | Content Negotiation chapter in the docs for details.
    |
    */
    'encoding' => [
        'application/vnd.api+json',
        'text/plain' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION,
    ],

    /*
    |--------------------------------------------------------------------------
    | Decoding Media Types
    |--------------------------------------------------------------------------
    |
    | This defines the media types that your API can receive from clients.
    | This array is keyed by expected media types, with the value being the
    | service binding that decodes the media type.
    |
    | These values are also used for Content Negotiation. If a client sends
    | a content type not listed here, it will receive a
    | 415 Unsupported Media Type response.
    |
    | Decoders can also be calculated at runtime, and/or you can add support
    | for media types for specific resources or requests. Refer to the
    | Content Negotiation chapter in the docs for details.
    |
    */
    'decoding' => [
        'application/vnd.api+json',
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
