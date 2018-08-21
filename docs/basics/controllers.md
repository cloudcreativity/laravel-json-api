# Controllers

## Introduction

This package contains a standard controller that can handle all JSON API endpoints for a resource without any
customisation. This means having a controller for a resource is optional. When you register routes,
the standard JSON API controller will be used by default.

You can extend the standard controller and use the hooks it provides to customise actions as needed for
specific resources. This is useful for dispatching events, jobs, etc on specific resources.

If the standard controller provided by this package does not meet your needs, you can create your own controller
as long as it implements the methods expected for the registered routes.

## Default Controller

The following route registration:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts');
});
```

Will use the `CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController` for the `posts` resource. This
will work for all controller actions without any customisation. So by default, no controller is needed.

## Extended Controller

If you need to customise the controller for a resource, for example to dispatch jobs or events from the controller,
you can extend the `JsonApiController`. When registering the resource routes you need to specify that a controller
is to be used:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', ['controller' => true]);
});
```

This will use the `PostsController` in the `Api` namespace. If you are using a different name for your controller,
you can specify it as follows:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', ['controller' => 'CustomPostsController']);
});
```

> The `namespace` option is identical to Laravel's namespace option when registering a route group.

Your controller would then look like this:

```php
namespace App\Http\Controllers\Api;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;

class PostsController extends JsonApiController
{
}
```

### Database Transactions

By default the controller will execute any modifications (i.e. `POST`, `PATCH` and `DELETE` requests) within
a database transaction on the default database connection. If you need to specify a different database connection,
set the `$connection` property on the controller:

```php
class PostsController extends JsonApiController
{
    protected $connection = 'other';
}
```

If you do not want to use transactions, set the `$useTransactions` property to `false`:

```php
class PostsController extends JsonApiController
{
    protected $useTransactions = false;
}
```

> If you need more control than this, overload the `transaction` method.

### Resource Hooks

The controller allows you to hook into the resource lifecycle by invoking the following methods if they are 
implemented:

- `searching`
- `reading`
- `saving`
- `creating`
- `updating`
- `created`
- `updated`
- `saved`
- `deleting`
- `deleted`

These methods allow you to easily implement application specific actions, such as firing events or dispatching
jobs.

The `searching` and `reading` hooks are invoked when resource(s) are being accessed, i.e. a `GET` request. The
`searching` hook is invoked when reading any resources (the *index* action), while `reading` is invoked when
reading a specific record (the *read* action).

> Note that the `reading` hook is **not** invoked if the request has filter parameters and there was no
matching record.

The `creating` and `created` hooks will be invoked when a resource is being created, i.e. a `POST` request. The
`updating` and `updated` hooks are invoked for a `PATCH` request on an existing resource. The `saving` and `saved`
hooks are called for both `POST` and `PATCH` requests.

The `searching` and `creating` hooks receive the JSON API request submitted by the client as their only argument, 
for example:

```php
use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest;

class PostsController extends JsonApiController
{

    protected function creating(ValidatedRequest $request)
    {
        // ...
    }
}
```

> The `creating` hook only receives the request because at the point it is invoked, the record does not exist.

The `reading`, `created`, `updating`, `updated`, `saved`, `deleting` and `deleted` hooks receive the domain record 
as their first argument, and the JSON API request as the second argument. For example:

```php
use App\Post;
use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest;

class PostsController extends JsonApiController
{

    protected function updated(Post $post, ValidatedRequest $request)
    {
        // ...
    }
}
```

The `saving` hook receives the same arguments (the record and the request). However the record will be `null` if
the resource is being created because it does not exist at this point. For example:

```php
use App\Post;
use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest;

class PostsController extends JsonApiController
{

    protected function saving(?Post $post, ValidatedRequest $request)
    {
        // ...
    }
}
```

### Relationship Hooks

The controller also allows you to hook into the relationship lifecycle by invoking the following methods if they are
implemented:

- `readingRelationship`
- `reading{Field}`
- `replacing`
- `replacing{Field}`
- `replaced{Field}`
- `replaced`
- `adding`
- `adding{Field}`
- `added{Field`
- `added`
- `removing`
- `removing{Field}`
- `removed{Field}`
- `removed`

These methods allow you to easily implement application specific actions, such as firing events or dispatching
jobs.

In the above method names `{Field}` refers to the camel-cased JSON API field name for the relationship. For example,
if reading the `author` relationship on a `posts` resource, the `readingRelationship` and/or `readingAuthor`
methods will be invoked if they exist.

The `reading...` methods are invoked when accessing the related resource or the relationship data, i.e. a `GET`
relationship request. The `replacing...` methods are invoked when changing the entire relationship in a
`PATCH` relationship request.

For *to-many* relationships, the `adding...` methods are invoked when adding resources to the relationship
using a `POST` relationship request. The `removing...` methods are invoked when removing resource from the
relationship using a `DELETE` relationship request.

All the relationship hooks receive the primary record being read as their first argument, and the JSON API request
received from the client as the second. For example:

```php
use App\Post;
use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest;

class PostsController extends JsonApiController
{

    protected function replacing(Post $post, ValidatedRequest $request)
    {
        // ...
    }

    protected function addingTags(Post $post, ValidatedRequest $request)
    {
        // ...
    }
}
```

### Responses

The standard controller returns responses for each controller action that comply with the JSON API specification
and are appropriate for the vast majority of use cases. If you need to return a different response, this can
be achieved by returning an instance of `Illuminate\Http\Response` from a controller hook.

> The controller has a `reply()` helper method for easily composing JSON API responses. For more information,
see the [chapter on Responses](../features/responses.md).

For example, if we wanted to send a `202 Accepted` response when a resource was deleted:

```php
/**
 * @param App\Post $record
 * @return Illuminate\Http\Response
 */
protected function deleted($record)
{
    return $this->reply()->meta([
        'accepted-at' => Carbon\Carbon::now()->toW3cString()
    ], 202);
}
```

This would result in the following HTTP response:

```http
HTTP/1.1 202 Accepted
Content-Type: application/vnd.api+json

{
  "meta": {
    "accepted-at": "2018-04-10T11:56:52+00:00"
  }
}
```

## Custom Controller

If the standard controller does not provide the functionality you require, you are able to write your own controller.
You will need to implement the controller actions listed below. We suggest that you look at the code for this
package's `JsonApiController` to see how these actions are implemented and what we are type-hinting in each
controller action.

### Resource Actions

| URL | Controller Action |
| :-- | :-- |
| `GET /posts` | `index` |
| `POST /posts` | `create` |
| `GET /posts/{record}` | `read` |
| `PATCH /posts/{record}` | `update` |
| `DELETE /posts/{record}` | `delete` |

### Relationship Actions

| URL | Controller Action |
| :-- | :-- |
| `GET /posts/{record}/comments` | `readRelatedResource` |
| `GET /posts/{record}/relationships/comments` | `readRelationship` |
| `PATCH /posts/{record}/relationships/comments` | `replaceRelationship` |
| `POST /posts/{record}/relationships/comments` | `addToRelationship` |
| `DELETE /posts/{record}/relationships/comments` | `removeFromRelationship` |
