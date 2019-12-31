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
JsonApi::register('default')->routes(function ($api, $router) {
    $api->resource('posts');
});
```

Will use the `CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController` for the `posts` resource. This
will work for all controller actions without any customisation. So by default, no controller is needed.

> Refer to the [Routing](./routing.md) chapter for details on how to change the default controller.

## Extended Controller

If you need to customise the controller for a resource, for example to dispatch jobs or events from the controller,
you can extend the `JsonApiController`. When registering the resource routes you need to specify that a controller
is to be used:

```php
JsonApi::register('default')->withNamespace('Api')->routes(function ($api, $router) {
    $api->resource('posts')->controller();
});
```

This will use the `PostsController` in the `Api` namespace. If you are using a different name for your controller,
you can specify it as follows:

```php
JsonApi::register('default')->withNamespace('Api')->routes(function ($api, $router) {
    $api->resource('posts')->controller('BlogPostsController');
});
```

> The `withNamespace` method is identical to Laravel's namespace method when registering a route group.

Your controller would then look like this:

```php
namespace App\Http\Controllers\Api;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;

class PostsController extends JsonApiController
{

  // ...
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
implemented. These methods allow you to easily implement application specific actions, such as firing events 
or dispatching jobs.

| Hook | Arguments | Request Class |
| :-- | :-- | :-- |
| `searching` | request | `FetchResources` |
| `searched` | results, request | `FetchResources` |
| `reading` | record, request | `FetchResource` |
| `didRead` | result, request | `FetchResource` |
| `saving` | record, request | `CreateResource` or `UpdateResource` |
| `creating` | request | `CreateResource` |
| `updating` | record, request | `UpdateResource` |
| `created` | record, request | `CreateResource` |
| `saved` | record, request | `CreateResource` or `UpdateResource` |
| `deleting` | record, request | `DeleteResource` |
| `deleted` | record, request | `DeleteResource` | 

> The request class is the validated request in the `CloudCreativity\LaravelJsonApi\Http\Requests` namespace.

The `searching`, `searched`, `reading` and `didRead` hooks are invoked when resource(s) are being accessed,
i.e. a `GET` request. The `searching` and `searched` hooks are invoked when reading any resources 
(the *index* action), while `reading` and `didRead` are invoked when reading a specific record
(the *read* action).

> Note that the `didRead` hook will receive a result of `null` if the request has filter parameters and
there was no matching record.

The `creating` and `created` hooks will be invoked when a resource is being created, i.e. a `POST` request. The
`updating` and `updated` hooks are invoked for a `PATCH` request on an existing resource. The `saving` and `saved`
hooks are called for both `POST` and `PATCH` requests.

> Note that the `saving` hook's first argument (the record) will be `null` when creating a resource.

Controller hooks are intended primarily for dispatching events or jobs. If you need to execute logic to fill
values into your domain record when creating or updating them, you should use [Adapter hooks](./adapters.md)
instead. This is because adapters are the classes that contain the logic to fill domain records.

### Relationship Hooks

The controller also allows you to hook into the relationship lifecycle by invoking the following methods if they are
implemented. These methods allow you to easily implement application specific actions, such as firing events or
dispatching jobs.

| Hook | Arguments | Request Class |
| :-- | :-- | :-- |
| `readingRelationship` | record, request | `FetchRelated` or `FetchRelationship` |
| `reading{Field}` | record, request | `FetchRelated` or `FetchRelationship` |
| `didRead{Field}` | record, related, request | `FetchRelated` or `FetchRelationship` |
| `didReadRelationship` | record, related, request | `FetchRelated` or `FetchRelationship` |
| `replacing` | record, request | `UpdateRelationship` |
| `replacing{Field}` | record, request | `UpdateRelationship` |
| `replaced{Field}` | record, request | `UpdateRelationship` |
| `replaced` | record, request | `UpdateRelationship` |
| `adding` | record, request | `UpdateRelationship` |
| `adding{Field}` | record, request | `UpdateRelationship` |
| `added{Field}` | record, request | `UpdateRelationship` |
| `added` | record, request | `UpdateRelationship` |
| `removing` | record, request | `UpdateRelationship` |
| `removing{Field}` | record, request | `UpdateRelationship` |
| `removed{Field}` | record, request | `UpdateRelationship` |
| `removed` | record, request | `UpdateRelationship` |

In the above method names `{Field}` refers to the camel-cased JSON API field name for the relationship. For example,
if reading the `author` relationship on a `posts` resource, the `readingRelationship` and `readingAuthor`
methods will be invoked if they exist.

The `reading...` and `didRead...` methods are invoked when accessing the related resource or the relationship data,
i.e. a `GET` relationship request. The `replacing...` and `replaced...` methods are invoked when changing the 
entire relationship in a `PATCH` relationship request.

For *to-many* relationships, the `adding...` and `added...` methods are invoked when adding resources to the
relationship using a `POST` relationship request. The `removing...` and `removed...` methods are invoked when
removing resource from the relationship using a `DELETE` relationship request.

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

## Custom Actions

The [Routing Chapter](./routing.md) describes how you can register custom routes in your API. For example
if we added an action to share a `posts` resource:

```php
JsonApi::register('default')->withNamespace('Api')->routes(function ($api) {
    $api->resource('posts')->controller()->routes(function ($posts) {
        $posts->post('{record}/share', 'share');
    });
});
```

This would expect the `share` method to be implemented on our resource's controller. For example:

```php
namespace App\Http\Controllers\Api;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;

class PostsController extends JsonApiController
{
    
    public function share(\App\Post $post): \Illuminate\Http\Response
    {
        \App\Jobs\SharePost::dispatch($post);
        
        return $this->reply()->content($post);
    }
}
```

When you do this, any query parameters sent by the client will be used when encoding the response. If you
have not validated the request, this could result in an error.

To avoid this, you will need to type-hint the JSON API request class to ensure the request is validated.
This package provides a number of request classes that validated the different *types* of request that
are defined by the JSON API specification. You should type-hint whichever is appropriate for your action.

> These request classes are validated when they are resolved out of the container. I.e. they work like
Laravel's form requests.

The example *share* action does not expect there to be any request body content, but it is going to return
a `posts` resource in the response. It is therefore the same as request to fetch a `posts` resource i.e.
`GET /api/posts/123`. (This is the case even if we have registered the action as needing to be called as
`POST /api/posts/123/share`.) We would therefore type-hint the `FetchResource` request object:

```php
namespace App\Http\Controllers\Api;

use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Http\Requests\FetchResource;

class PostsController extends JsonApiController
{
    
    public function share(FetchResource $request, \App\Post $post): \Illuminate\Http\Response
    {
        \App\Jobs\SharePost::dispatch($post);
        
        return $this->reply()->content($post);
    }
}
```

All request classes are in the `CloudCreativity\LaravelJsonApi\Http\Requests` namespace. These are
the ones available:

| Action | Request Class |
| :-- | :-- |
| `index` | `FetchResources` |
| `create` | `CreateResource` |
| `read` | `FetchResource` |
| `update` | `UpdateResource` |
| `delete` | `DeleteResource` |
| `readRelatedResource` | `FetchRelated` |
| `readRelationship` | `FetchRelationship` |
| `replaceRelationship` | `UpdateRelationship` |
| `addToRelationship` | `UpdateRelationship` |
| `removeFromRelationship` | `UpdateRelationship` |

> All of these classes extended the `ValidatedRequest` abstract class. If none of them do exactly what you
need for your custom action, you can write you own request class that extends the abstract class.

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
