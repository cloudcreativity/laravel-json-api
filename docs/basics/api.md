# APIs

## Introduction

This package allows your application to have one (or many) APIs that conform to the JSON API spec. Each API is
given a name, and configuration is held on a per-API basis.

The default API name is `default`. You can change the default name via the JSON API facade by adding
the following to the `boot()` method of your `AppServiceProvider`:

```php
public function boot()
{
    JsonApi::defaultApi('v1');
}
```

## Generating an API

To generate your first API in your application:

```bash
$ php artisan make:json-api
```

This uses the default API name and generates a config file called `json-api-{name}.php`.

### Multiple APIs

If your application has multiple APIs - e.g. if you have a version controlled API - you must generate a config file for 
each API. For example:

```bash
$ php artisan make:json-api v1
```

Will create the `json-api-v1.php` file.

## Namespacing

### Root Namespace

Your API's config file contains a `namespace` option that controls the namespace in which JSON API classes are held.

If `namespace` is `null`, the `JsonApi` namespace in your application's namespace will be used. E.g. in a default
Laravel installation, the namespace will be `App\JsonApi`. If you have renamed your application namespace to
`MyApp`, then `MyApp\JsonApi` will be used by default.

If you want to use a different namespace, set the `namespace` option accordingly. E.g. for our `v1` API, we might
want to set it to `App\JsonApi\V1`.

### Organising Resource Classes

The `by-resource` setting controls how you want to organise classes within this namespace. If this setting is `true`,
there will be a sub-namespace for each resource type. For example:

```text
App\JsonApi
  - Posts
    - Adapter
    - Schema
    - Validators
  - Comments
    - Adapter
    - Schema
    - Validators
```

If `by-resource` is `false`, the sub-namespace will be the class type (e.g. `Adapters`). For example:

```text
App\JsonApi
  - Adapters
    - PostAdapter
    - CommentAdapter
  - Schemas
    - PostSchema
    - CommentSchema
  - Validators
    - PostValidator
    - CommentValidator
```

You must stick to whatever pattern you choose to use. This is because we use the structure to automatically detect
JSON API classes.

### Eloquent

The config also contains a `use-eloquent` option. Set this to `true` if the majority of your resources relate to
Eloquent models.

This option is used by the package's generators, so that they know to generate Eloquent JSON API classes or not. This
saves you having to specify the type whenever generating JSON API classes.

The `use-eloquent` option is effectively a default, and can be overridden when using a generator. For example, if
`use-eloquent` is `true`:

```bash
# will generate Eloquent classes
$ php artisan make:json-api:resource posts
# will generate non-Eloquent classes
$ php artisan make:json-api:resource posts -N
```

If `use-eloquent` is `false`:

```bash
# will generate non-Eloquent classes
$ php artisan make:json-api:resource posts
# will generate Eloquent classes
$ php artisan make:json-api:resource posts -e
```

## Defining Resources

Your API must be configured to understand how a JSON API resource type maps to a PHP class within your application. 
This is defined in the `resources` setting in the API's configuration file.

For example, if your application had two Eloquent models - `Post` and `Comment` - your resource configuration would be:

```php
// config/json-api-default.php
// ...
'resources' => [
  'posts' => \App\Post::class,
  'comments' => \App\Comment::class,
]
```

You can also map a resource type to multiple PHP classes as follows:

```php
'resources' => [
    // ...
    'tags' => [\App\UserTag::class, \App\SystemTag::class],
]
```

## URL

Each JSON API is expected to have a root URL under which all its routes are nested. This is configured in your API's
configuration file under the `url` setting, that looks like this:

```php
'url' => [
    'host' => null,
    'namespace' => '/api/v1',
    'name' => 'api:v1:',
],
```

These settings control the links that appear in JSON API documents. We also automatically apply them when you
register routes for your API.

### Host

When processing inbound HTTP requests, the current server host will be used when encoding JSON API documents.

When encoding JSON API documents outside of HTTP requests, we use the `url.host` option from your API's configuration.
If the value is `null`, we default to Laravel's `app.url` config setting. Otherwise, we'll use the value you've
provided.

If you do not want the host to be appended to URLs in the encoded document, set `url.host` to `false`.

### Namespace

The URL namespace is the URL under which all resources for the API are nested. For example, if the namespace is
`/api/v1`, then the `posts` resource routes will exists at `/api/v1/posts`.

### Name

The `name` setting applies the specified prefix to all route names that are registered for JSON API resources. For
example, if the `name` is `api:v1:`, then the route name for the index of the `posts` resource will be
`api:v1:posts.index`.

## Content Negotiation

The JSON API spec defines [content negotiation](http://jsonapi.org/format/#content-negotiation) that must occur
between the client and the server. This is handled by this package based on your API's `codecs` configuration.

The generated API file contains a sensible default, for example:

``` php
'codecs' => [
  'encoders' => [
    'application/vnd.api+json',
    'text/plain' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
  ],
  'decoders' => [
    'application/vnd.api+json',
  ],
],
```

### Encoders

In the example, the config tells the codec matcher that the `application/vnd.api+json` and
`text/plain` are valid `Accept` headers, along with how to encode responses for each type. If the client sends an 
`Accept` media type that is not recognised, a `406 Not Acceptable` response will be sent.

> The options for how to encode responses for each media type are the same as the options for PHP's `json_encode()` 
function.

### Decoders

In the example, the config tells the codec matcher that the `application/vnd.api+json` is the only acceptable
`Content-Type` that a client can submit. If a different media type is received, a `415 Unsupported Media Type`
response will be sent.
