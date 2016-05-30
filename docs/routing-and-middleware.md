# Routing

## Route Groups

To define JSON API endpoints, the JSON API middleware must be used. This is easily done by using route groups, for
example:

``` php
Route::group(['middleware' => 'json-api'], function () {
  // define JSON-API routes here.
});
```

This middleware takes two *optional* parameters:

1. The URL namespace to add to the HTTP scheme and host when encoding JSON API links. For example, if your
JSON API endpoints are at `http://www.example.tld/api/v1`, then the URL namespace is `/api/v1`.
2. The name of the schemas set you wish to use (if your application is using multiple schema sets).
More info on that in the `encoding` chapter of the documentation.s

For example, the following sets the `/api/v1` namespace, and uses the `v1` schema set:

``` php
Route::group(['middleware' => 'json-api:/api/v1,v1'], function () {
  // define JSON-API routes here.
});
```

If every route in your application is a JSON API route, you can install the middleware on your HTTP Kernel.

> The `json-api` middleware cannot be used as controller middleware because it needs to do its work before
a JSON API request object is created. Normally your controller will have the JSON API request object injected
into its constructor. (Refer to the chapter on controllers.)

## Defining Endpoints

The JSON API spec defines the endpoints that should exist for each resource type, and the HTTP methods that
relate to these. Defining resource object endpoints is as easy as:

``` php
Route::group(['middleware' => 'json-api'], function () {
    JsonApi::resource('posts', 'Api\PostsController');
    JsonApi::resource('people', 'Api\PeopleController');
});
```

> If you are not using the facade, your will need an instance of
`CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar` from the service container. Call the `resource()` method
with the same arguments as shown above.

### URLs and Controller Methods

Per resource type, the following endpoints will be registered (using the `posts` resource type in the example above):

| URL | Controller Method |
| :-- | :---------------- |
| `GET /posts` | `index()` |
| `POST /posts` | `create()` |
| `GET /posts/{resource_id}` | `read($resourceId)` |
| `PATCH /posts/{resource_id}` | `update($resourceId)` |
| `DELETE /posts/{resource_id}` | `delete($resourceId)` |
| `GET /posts/{resource_id}/{relationship_name}` | `readRelatedResource($resourceId, $relationshipName)` |
| `GET /posts/{resource_id}/relationships/{relationship_name}` | `readRelationship($resourceId, $relationshipName)` |
| `PATCH /posts/{resource_id}/relationships/{relationship_name}` | `replaceRelationship($resourceId, $relationshipName)` |
| `POST /posts/{resource_id}/relationships/{relationship_name}` | `addToRelationship($resourceId, $relationshipName)` |
| `DELETE /posts/{resource_id}/relationships/{relationship_name}` | `removeFromRelationship($resourceId, $relationshipName)` |

You do not need to implement all of these methods if you are extending your controller from
`CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController`. That controller sends a `501 Not Implemented`
response by default for all methods, so you just need to overload the methods that you want to implement.

> The `relationship_name` parameter is validated via JSON-API request objects. See the chapter titled on Requests
for details.

### Route Names

Per resource type, the following route names are registered (using the `posts` resource type as an example):

| URL | Route Name |
| :-- | :-- |
| `/posts` | `posts.index` |
| `/posts/{resource_id}` | `posts.resource` |
| `/posts/{resource_id}/{relationship_name}` | `posts.related` |
| `/posts/{resource_id}/relationships/{relationship_name}` | `posts.relationships` |

Remember that if your route group has a name prefix, then the route names in the table above will be prefixed. For
example:

``` php
Route::group([
  'middleware' => 'json-api',
  'as' => 'api::',
], function () {
    JsonApi::resource('posts', 'Api\PostsController');
});
```

The `/posts` URL will have this route name: `api::posts.index`

### Generating Links

You can use these route names to generate [JSON API link](http://jsonapi.org/format/#document-links) by using our
`CloudCreativity\LaravelJsonApi\Document\GeneratesLinks` trait, which adds the `linkTo()` helper to your class.

``` php

/** @var Neomerx\JsonApi\Contracts\Document\LinkInterface $link */
$link = $this->linkTo()->index('api::posts');
$link = $this->linkTo()->resource('api::posts', '1');
$link = $this->linkTo()->relatedResource('api::posts', '1', 'author');
$link = $this->linkTo()->relationship('api::posts', '1', 'author');
```

All these methods accept two optional arguments. The first is additional route/query parameters, and the second
is `meta` to attach to the generated link object. For example:

``` php
$link = $this->linkTo()->index(
  'api::posts',
  // query parameters
  ['page' => ['number' => 1]],
  // meta
  ['foo' => 'bar']
);
```

## Middleware

The `json-api` middleware effectively boots JSON API support for the routes on which it is applied. As part of this
boot process it:

1. Ensures content negotiation occurs in compliance with the spec - see below.
2. Sends an appropriate HTTP error status (`415`, `406`) if the content negotiation does not pass.
3. Registers a schema container that has the set of schemas that are being used for this route group.

By registering a schema set, there is knowledge in any subsequent part of the application that it is in a JSON API
route. If you need to check whether you are in a JSON API route, you can do this via the service:

``` php
/** @var CloudCreativity\LaravelJsonApi\Services\JsonApiService $service */
$service->isJsonApi();
```

Or via the facade:

``` php
JsonApi::isJsonApi();
```

## Content Negotiation

The JSON API spec defines [content negotiation](http://jsonapi.org/format/#content-negotiation) that must occur
between the client and the server. This is implemented via a *codec matcher*.

> The codec matcher is provided from the `neomerx/json-api` dependency and defined in the following interface:
`Neomerx\JsonApi\Contracts\Codec\CodecMatcherInterface`

### Configuration

Content negotiation is configurable under the `codec-matcher` key in the `json-api.php` config file.

For example:

``` php
'codec-matcher' => [
    'encoders' => [
        'application/vnd.api+json',
        'text/plain' => JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES,
    ],
    'decoders' => [
        'application/vnd.api+json',
    ],
],
```

> Loading of encoders/decoders from configuration arrays is provided from the `cloudcreativty/json-api`
dependency and defined in the following interface:
`CloudCreativity\JsonApi\Contracts\Repositories\CodecMatcherRepositoryInterface`

#### `codec-matcher.encoders`

In the example, the config tells the codec matcher that the `application/vnd.api+json` and
`text/plain` are valid `Accept` headers, along with how to encode responses for each type. If the client sends an
`Accept` media type that is not recognised, a `406 Not Acceptable` response will be sent.

#### `codec-matcher.decoders`

In the example, the config tells the codec matcher that the `application/vnd.api+json` is the only acceptable
`Content-Type` that a client can submit. If a different media type is received, a `415 Unsupported Media Type`
response will be sent.
