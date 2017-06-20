# APIs

## Generating an API

To generate your first API in your application:

```bash
$ php artisan make:json-api
```

This uses the name `default` for your API and generates a config file called `json-api-default.php`.

### Multiple APIs

If your application has multiple APIs - e.g. if you've version controlled a public API - you must generate a config file for each API. For example:

```bash
$ php artisan make:json-api v1
```

Will create the `json-api-v1.php` file.

## Defining Resources

Your API must be configured to understand how a JSON API resource type maps to a PHP class within your application. This is defined in the `resources` setting in the API's configuration file.

For example, if your application had two Eloquent models - `Post` and `Comment` - your resource configuration would be:

```php
// config/json-api-default.php
// ...
'resources' => [
  'posts' => \App\Post::class,
  'comments' => \App\Comment::class,
]
```

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
`text/plain` are valid `Accept` headers, along with how to encode responses for each type. If the client sends an `Accept` media type that is not recognised, a `406 Not Acceptable` response will be sent.

> The options for how to encode responses for each media type are the same as the options for PHP's `json_encode()` function.

### Decoders

In the example, the config tells the codec matcher that the `application/vnd.api+json` is the only acceptable
`Content-Type` that a client can submit. If a different media type is received, a `415 Unsupported Media Type`
response will be sent.

## Other Configuration Settings

The generated API configuration file contains descriptions of each setting. The wiki will cover these settings in the relevant chapter.
