Add [jsonapi.org](http://jsonapi.org) compliant APIs to your Laravel 5 application. Based on the framework agnostic packages [neomerx/json-api](https://github.com/neomerx/json-api) and [cloudcreativity/json-api](https://github.com/cloudcreativity/json-api).

## What is JSON API?

From [jsonapi.org](http://jsonapi.org)

> If you've ever argued with your team about the way your JSON responses should be formatted, JSON API is your anti-bikeshedding weapon.
>
> By following shared conventions, you can increase productivity, take advantage of generalized tooling, and focus on what matters: your application. Clients built around JSON API are able to take advantage of its features around efficiently caching responses, sometimes eliminating network requests entirely.

For full information on the spec, plus examples, see http://jsonapi.org

## Features

* Encoding of JSON API responses using the `neomerx/json-api` package.
* Start JSON API support on route groups through a single piece of middleware, which automatically checks request headers and loads JSON API query parameters.
* Registration of JSON API defined resource end-points via a simple route helper.
* Define supported JSON API extensions via middleware (on route groups or individual controllers).
* A JSON API controller providers helpers to:
  - Automatically check request query parameters.
  - Decode request body content into objects with a standard, fluent, interface. Makes handling content easier.
  - Validate request body content as it is being decoded, including using Laravel validators on resource object attributes.
  - Helpers for sending common JSON API responses, that automatically include requested encoding parameters, the encoded media type plus registered supported extensions.
* Rendering of exceptions into JSON API error responses, plus easy sending of error responses via throwable JSON API error objects.
* Configuration settings - including schemas, encoders, decoders and exception rendering - all defined in a configuration file in your Laravel application.

## Status

This repository is under development but is considered relatively stable. Tagged versions exist and we have live
applications that are using the repository.

## License

Apache License (Version 2.0). Please see [License File](LICENSE) for more information.

## Installation

Install using [Composer](http://getcomposer.org):

``` bash
$ composer require cloudcreativity/laravel-json-api
```

Add the package service provider to your `config/app.php` providers array. It is important that it is added **before** your application's route service provider.

``` php
\CloudCreativity\JsonApi\ServiceProvider::class
```

Plus add the following to the list of aliases in the same file (`config/app.php`) so that you can use the `JsonApi` facade:

``` php
'aliases' => [
  // ... existing aliases
  'JsonApi' => CloudCreativity\JsonApi\Facade::class
]
```

Then publish the package config file:

``` bash
$ php artisan vendor:publish --provider="CloudCreativity\JsonApi\ServiceProvider"
```
> Configuration settings are describe in the usage section below.


## Usage

- **Configuration keys** are stored in constants on the `CloudCreativity\JsonApi\Config` class (and will be referred to
as **`C::`** below).

### Routing

To define JSON API endpoints, the JSON API middleware must be used. This is easily done by using route groups, for
example:

``` php
Route::group(['middleware' => 'json-api'], function () {
  // define JSON-API routes here.
});
```

This middleware takes two optional parameters. The first is a URL namespace to add to the HTTP scheme and host when
encoding JSON API links. For example, if your JSON API endpoints are at `http://www.example.tld/api/v1`, then the
URL namespace is `/api/v1`. The second middleware argument is the name of the schemas set you wish to use (if your
application is using multiple schema sets).

For example, the following sets the `/api/v1` namespace, and uses the `extra-schemas` schema set:

``` php
Route::group(['middleware' => 'json-api:/api/v1,extra-schemas'], function () {
  // define JSON-API routes here.
});
```

If every route in your application is a JSON API endpoint, then you can set the `C::IS_GLOBAL` option to `true`. This
will install the same piece of middleware on the HTTP Kernel, so that it is invoked for every request.

#### Defining Endpoints

The JSON API spec defines the endpoints that should exist for each resource object type, and the HTTP methods that
relate to these. Defining resource object endpoints is as easy as:

``` php
Route::group(['middleware' => 'json-api'], function () {

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
| /articles/:id/relationships/author | GET | `readAuthorRelationship($id)` |
| /articles/:id/relationships/author | PATCH | `updateAuthorRelationship($id)` |
| /articles/:id/comments | GET | `readComments($id)` |
| /articles/:id/relationships/comments | GET | `readCommentsRelationship($id)` |
| /articles/:id/relationships/comments | PATCH | `updateCommentsRelationship($id)` |
| /articles/:id/relationships/comments | DELETE | `deleteCommentsRelationship($id)` |

**You do not need to implement all these methods** if extending this package's `Http\Controllers\JsonApiController`.
The controller is configured to send a `501 Not Implemented` response for any missing methods.

### The Middleware

The `json-api` middleware effectively boots JSON API support for the routes on which it is applied. As part of this
boot process it:

1. Loads schemas from you configuration.
2. Creates a `CodecMatcherInterface` from your configuration.
3. Creates a `ParametersInterface` from the received request.
4. Checks that the request headers match to an encoder in the codec matcher.
5. Parses the request query parameters.

If the checks pass, then generated instances are registered. These can be accessed through the `JsonApi` facade, e.g.:

* `JsonApi::getSchemas()` returns the schema set for the current request.
* `JsonApi::getEncoder()` returns the encoder for the current request.
* `JsonApi::getParameters()` returns the parsed JSON API parameters for the current request.

Other methods are also available - see the `CloudCreativity\JsonApi\Contracts\Integration\EnvironmentInterface` for
available methods.

Exceptions will be thrown if the checks do not pass. If at any point you need to check whether the middleware was run
(i.e. whether you're currently in a JSON API route), then use the `JsonApi::hasSchemas()` method.

### Supported Extensions

To register supported extensions, use the `json-api-ext` middleware. This takes middleware parameters that list
the extensions supported. For example:

``` php
Route::group([
    'middleware' => ['json-api', 'json-api-ext:ext1,ext2']
  ], function () {
    // JSON API routes here, all supporting the above extensions.
});
```

Middleware makes it easy to register multiple routes (or even an entire API) that support the same extensions.
Alternatively, you can use the supported extension middleware as controller middleware if desired.

### Controller

JSON API support in controllers can be added by extending `CloudCreativity\JsonApi\Http\Controllers\JsonApiController`.
This has a number of helper methods to assist in handling JSON API requests and sending responses.

Each of the following pieces of functionality are implemented using traits. So if you want to include any of the
functionality in your own custom controllers, just include the relevant trait. The traits are in the same namespace as
the `JsonApiController`.

#### Query Parameters

The `JsonApiController` will automatically check the query parameters before your controller action method is invoked.
To define what query parameters your controller allows, set the properties that are defined in the `QueryCheckerTrait`.

This automatic checking means that by the time your controller action method is invoked, the request headers and query
parameters have all been checked. Your action method can get the query parameters by calling `$this->getParameters()`.

If you want to disable automatic checking of query parameters before your controller methods are invoked, then set the
`$autoCheckQueryParameters` property of your controller to `false`.

Note that if you are adding the trait to your own custom controller, you will need to call `$this->checkParameters()`
to trigger the checking of parameters.

#### HTTP Content Body

To decode the request content body with the decoder that matched the request `Content-Type` header, then call
`$this->getContentBody()`.

If you want to use a `CloudCreativity\JsonApi\Contracts\Object\DocumentInterface` object to handle the request content
in your controller action, use `$this->getDocumentObject()`. This method ensures the decoder has returned a
`DocumentInterface` object, or if it has returned a `stdClass` object it will be cast to a `DocumentInterface` object.
Shorthands are also provided if you are expecting the document to contain a resource object in its data member, or if
the provided document represents a relationship. Use `$this->getResourceObject()` and `$this->getRelationshipObject()`
respectively.

These helper methods are implemented in the `DocumentDecoderTrait`.

#### Content Body Validation

If desired, you can validate the body content as it is decoded. All of the content body getter methods accept an
optional validator that is applied when the document content is decoded. All of methods for getting the request body
content take an optional validator argument.

If the received content is a resource object, you can use the `$this->getResourceObjectValidator()` method. For example:

``` php
class ArticlesController extends JsonApiController
{
  // ...

  public function update($id)
  {
    $validator = $this
      ->getResourceObjectValidator(
        // the expected resource type
        'articles',
        // the expected id (use null for new resources)
        $id,
        // the rules for the attributes - uses the Laravel validation implementation.
        [
          'title' => 'string|max:250',
          'content' => 'string',
          'draft' => 'boolean',
        ],
        // Laravel validation messages, if you need to customise them
        [],
        // whether the attributes member must be present in the resource object
        true
      );

   $object = $this->getResourceObject($validator);
   // ...
  }
}
```

The validator returned by `$this->getResourceObjectValidator()` provides helper methods for also defining relationship
validation rules.

For relationship endpoints, you can get a validator for the relationship provided using one of the following helper
methods:

``` php
class ArticlesController extends JsonApiController
{
  // ...

  public function updateAuthorRelationship($id)
  {
    $validator = $this->getHasOneValidator('person');
    $relationship = $this->getRelationshipObject($validator);
    // ...
  }

  public function updateCommentsRelationship($id)
  {
    $validator = $this->getHasManyValidator('comments');
    $relationship = $this->getRelationshipObject($validator);
    // ...
  }
}
```

These helper methods are provided by the `DocumentValidatorTrait`, which uses the `DocumentDecoderTrait`.

#### Responses

The class `CloudCreativity\JsonApi\Http\Responses\ResponsesHelper` provides a number of methods for constructing JSON
API responses. This helper automatically uses request encoding parameters, the matched encoding media type plus any
registered supported extensions.

If you have extended the `JsonApiController`, you can use this helper by calling `$this->reply()` then the method for
the type of response you want to send. For example, to send a content response:

``` php
class ArticlesController extends JsonApiController
{
  // ...

  public function read($id)
  {
    $article = Article::find($id);

    if (!$article) {
      $this->notFound();
    }

    return $this
      ->reply()
      ->content($article);
  }
}
```

The available helpers are:

| Method | Detail |
| :----- | :----- |
| `statusCode` | Send a status code only reply |
| `noContent` | Send a no content reply (204) |
| `meta` | Send meta only |
| `content` | Send content (resource object, null, or collection) |
| `created` | Send a resource created response (encoded object, location header and 201 status) |
| `relationship` | Send a relationship response (encoded identifier, null, or collection of identifiers) |
| `error` | Send a response with a single JSON API error object |
| `errors` | Send a response with multiple JSON API error objects |

See the class for the parameters that each helper method accepts.

Note that sending error responses can also be achieved by throwing exceptions (see below). Throwing errors
is preferable if you want to stop execution, and/or if you want your exception handler to decide if the exception
should be logged. Using the reply helper's `error` and `errors` methods will not send an exception to your exception
handler.

The reply method is added to the controller via the `ReplyTrait`.

### Exception Handling

To add JSON API support to your application's Exception Handler, add the `Exceptions\HandlerTrait` to your
`App\Exceptions\Handler` instance. Then, in your `render()` method you can do the following:

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

You can configure the exception renderer in your `json-api` config file under the `C::EXCEPTIONS` key. This takes a
default HTTP status (which is `500` if not provided) plus a map of exceptions. The map should be an array of Exception
class names as keys, with their values either being an HTTP status code or an array representing the JSON API Error
object to return. For example:

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

If providing an array template, then remember to include an `Error::STATUS` code so that the HTTP status of the
response is set correctly.

#### Sending JSON API Error Responses

If during the course of your application's logic you need to return a JSON API error object to the client, throw one of
the following exceptions:

* `CloudCreativity\JsonApi\Error\ThrowableError` - an exception that is an error object.
* `CloudCreativity\JsonApi\Error\ErrorException` - an exception that takes a `Neormerx\JsonApi\Contracts\Document\ErrorInterface`
object as its first argument, effectively allowing you to throw an error object. An error object implementation is
available - use `CloudCreativity\JsonApi\Error\ErrorObject`.
* `CloudCreativity\JsonApi\Error\MultiErrorException` - an exception that takes a
`CloudCreativity\Jsonapi\Contracts\Error\ErrorCollectionInterface` object as its first object.

None of the above classes need to be registered in your config file's exception map because the renderer is
automatically configured to handle them.
