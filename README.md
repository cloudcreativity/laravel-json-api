Add [jsonapi.org](http://jsonapi.org) compliant APIs to your Laravel 5 application. Based on the framework agnostic packages [neomerx/json-api](https://github.com/neomerx/json-api) and [cloudcreativity/json-api](https://github.com/cloudcreativity/json-api).

## What is JSON API?

From [jsonapi.org](http://jsonapi.org)

> If you've ever argued with your team about the way your JSON responses should be formatted, JSON API is your anti-bikeshedding weapon.
>
> By following shared conventions, you can increase productivity, take advantage of generalized tooling, and focus on what matters: your application. Clients built around JSON API are able to take advantage of its features around efficiently caching responses, sometimes eliminating network requests entirely.

For full information on the spec, plus examples, see http://jsonapi.org

## Features

@todo

## Installation

Install using [Composer](http://getcomposer.org):

``` bash
$ composer require cloudcreativity/laravel-json-api
```

Then publish the package config file:

``` bash
$ php artisan vendor:publish --provider="CloudCreativity\JsonApi\ServiceProvider"
```
> Configuration settings are describe in the usage section below.

Add the package service provider to your `config/app.php` providers array:

``` php
\CloudCreativity\JsonApi\ServiceProvider::class
```

And add the following to the list of aliases in the same file (`config/app.php`) so that you can use the `JsonApi` facade:

``` php
'aliases' => [
  // ... existing aliases
  'JsonApi' => CloudCreativity\JsonApi\Facade::class
]
```

## Usage

- **Configuration keys** are stored in constants on the `CloudCreativity\JsonApi\Config` class (and will be referred to as **`C::`** below).
- **Middleware names** are stored in the `CloudCreativity\JsonApi\Middleware` class (and will be referred to as **`M::`** below). This class also has some static methods for easily constructing middleware names with middleware options.


### Routing

To define JSON API endpoints, the `M::JSON_API` middleware must be used. This is easily done by using route groups, for example:

``` php
Route::group(['middleware' => M::JSON_API], function () {
  // define JSON-API routes here.
});
```

If every route in your application is a JSON API endpoint, then you can set the `C::IS_GLOBAL` option to true. This will install the same piece of middleware on the HTTP Kernel, so that it is invoked for every request.

#### Defining Endpoints

The JSON API spec defines the endpoints that should exist for each resource object type, and the HTTP methods that relate to these. Defining resource object endpoints is as easy as:

``` php
Route::group(['middleware' => M::JSON_API], function () {

    JsonApi::resource('articles', 'Api\ArticlesController', [
    	'hasOne' => ['author'],
    	'hasMany' => ['comments'],
    ]);
    JsonApi::resource('people', 'Api\PeopleController');
});
```
Per resource type, the following endpoints will be registered (using the `articles` resource type in the example above):

| URL | HTTP Method | Controller Method |
| :-- | :---------- | :---------------- |
| /articles | GET | `index()` |
| /articles | POST | `create()` |
| /articles/:id | GET | `read($id)` |
| /articles/:id | PATCH | `update($id)` |
| /articles/:id | DELETE | `delete($id)` |
| /articles/:id/author | GET | `readAuthor($id)` |
| /articles/:id/relationships/author | GET | `readAuthorIdentifier($id)` |
| /articles/:id/relationships/author | PATCH | `updateAuthorIdentifier($id)` |
| /articles/:id/comments | GET | `readComments($id)` |
| /articles/:id/relationships/comments | GET | `readCommentIdentifiers($id)` |
| /articles/:id/relationships/comments | PATCH | `updateCommentIdentifiers($id)` |
| /articles/:id/relationships/comments | DELETE | `deleteCommentIdentifiers($id)` |

**You do not need to implement all these methods** if extending this package's `Http\Controllers\JsonApiController`. The controller is configured to send a `501 Not Implemented` response for any missing methods.

### The Middleware

The `M::JSON_API` middleware effectively boots JSON API support for the routes on which it is applied. As part of this boot process it:

1. Creates a `CodecMatcherInterface` from your configuration.
2. Creates a `ParametersInterface` from the received request.
3. Checks that the request headers match to an encoder in the codec matcher.
4. Parses the request query parameters.

If the checks pass, then the codec matcher instance and parameters instances are registered on the `JsonApi` service. These can be accessed through the `JsonApi` facade:

* `JsonApi::getCodecMatcher()` returns a `Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface` instance.
* `JsonApi::getParameters()` returns a `Neomerx\JsonApi\Contracts\Parameters\ParametersInterface` instance.

Exceptions will be thrown if the checks do not pass.

If at any point you need to check whether the middleware was run (i.e. whether you're currently in a JsonApi route), then use the `JsonApi::isActive()` method.

### Supported Extensions

To register supported extensions, use the `M::SUPPORTED_EXT` middleware. This takes middleware parameters that list the extensions supported. A static method `M::ext()` allows the middleware name to be easily composed. For example:

``` php
Route::group([
    'middleware' => [M::JSON_API, M::ext('ext1', 'ext2')]
  ], function () {
    // JSON API routes here, all supporting the above extensions.
});
```

Middleware makes it easy to register multiple routes (or even an entire API) that support the same extensions. Alternatively, you can use the supported extension middleware as controller middleware if desired.

### Controller

#### Query Parameters

#### HTTP Content Body

#### Content Body Validation

### Responses

### Exception Handling

To add JSON API support to your application's Exception Handler, add the `Exceptions\HandlerTrait` to your `App\Exceptions\Handler` instance. Then, in your `render()` method you can do the following:

``` php
namespace App\Exceptions;

use CloudCreativity\JsonApi\Exceptions\HandlerTrait;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
	use HandlerTrait;

	// ...

    public function render($request, \Exception $e)
    {
    	if ($this->isJsonApi()) {
        	return $this->renderJsonApi($request, \Exception $e);
        }

        // do standard exception rendering here...
    }
}
```

#### Configuring Exception Rendering

You can configure the exception renderer in your `json-api` config file under the `C::EXCEPTIONS` key. This takes a default HTTP status (which is `500` if not provided) plus a map of exceptions. The map should be an array of Exception class names as keys, with their values either being an HTTP status code or an array representing the JSON API Error object to return. For example:

``` php

use CloudCreativty\JsonApi\Exceptions\StandardRenderer as Renderer;
use CloudCreativity\JsonApi\Contracts\Error\ErrorObjectInterface as Error;

[
  // ... other config

  C::EXCEPTIONS => [

    Renderer::DEFAULT_STATUS => 500,
    Renderer::MAP => [
      'FooException' => 504,
      'BarException' => [
        Error::TITLE => 'Teapot',
        Error::DETAIL => "I'm a teapot, not a server.",
        Error::STATUS => 418,
      ],
    ],
  ],
];
```

If providing an array template, then remember to include an `Error::STATUS` code so that the HTTP status of the response is set correctly.

#### Sending JSON API Error Responses

If during the course of your application's logic you need to return a JSON API error object to the client, throw one of the following exceptions:

* `CloudCreativity\JsonApi\Error\ThrowableError` - an exception that is an error object.
* `CloudCreativity\JsonApi\Error\ErrorException` - an exception that takes a `Neormerx\JsonApi\Contracts\Document\ErrorInterface` object as its first argument, effectively allowing you to throw an error object.
* `CloudCreativity\JsonApi\Error\MultiErrorException` - an exception that takes a `CloudCreativity\Jsonapi\Contracts\Error\ErrorCollectionInterface` object as its first object.

None of the above classes need to be registered in your config file's exception map because the renderer automatically handles them.
