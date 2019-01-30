# Security

## Introduction

This package provides the ability to [authenticate](https://laravel.com/docs/authentication) and 
[authorize](https://laravel.com/docs/authorization) users to your JSON API in a number of ways:

- Middleware: use Laravel's in-built `auth` middleware to authenticate users to your API, or any custom
middleware you use for your application.
- Authorizers: classes that contain logic for authorizing and authenticating JSON API requests. These
can either be re-used across multiple resource types, or be implemented for a specific resource type.
- Controller Authorization: use [controller hooks](./controllers.md) to authorize requests from within your
resource's controller.

It is important to note that middleware and authorizers will authorize the JSON API request *before* it
has been validated. Controller authorization occurs *after* validation and should therefore be used if your
authorization is reliant on any JSON API content or parameters within the request.

## Middleware Authentication

When registering JSON API routes, you can apply middleware that authorizes the inbound request.

For example, Laravel applications come with the `auth` middleware. You can use this to authenticate users
to your API and protect either the whole API or specific resources. If your API only returned resources for the
authenticated user, you could protect the entire API using the `auth` middleware as follows:

```php
JsonApi::register('default')->middleware('auth')->routes(function ($api, $router) {
   // ...
});
```

This will apply the `auth` middleware to *every* JSON API route within your API.

If certain resources within your API only ever related to the authenticated user, you can protect the specific
resources as follows:

```php
JsonApi::register('default')->routes(function ($api, $router) {
   $api->resource('posts'); // not protected
   $api->resource('user-profiles')->middleware('auth'); // protected
});
```

This will apply the `auth` middleware to every `user-profiles` resource route.

## Authorizers

If you need to run different authentication and authorization logic for the different JSON API resource actions,
then you can define your logic in *authorizer* classes. Authorizers can either be re-used across multiple
resource types, or defined for a specific resource type.

As well as defining your authorization logic in a single class, they also contain a number of helper methods
(described below) to make authentication and authorization easy.

### Creating Authorizers

To generate an authorizer that is re-usable across multiple JSON API resource types, use the following:

```bash
$ php artisan make:json-api:authorizer <name> [<api>]
```

Where `<name>` is a unique name for the authorizer, e.g. `default` for an authorizer we will use as our
default authorization logic.

To generate a resource specific authorizer, use the resource type as the name and add the `--resource` (or `-r`)
flag. E.g. to generate an authorizer for our `posts` resource:

```bash
$ php artisan make:json-api:authorizer posts -r
```

Alternatively you can generate an authorizer when creating a resource using the `--auth` (or `-a`) flag:

```bash
$ php artisan make:json-api:resource posts --auth
```

If your API has its `by-resource` option set to `true`, the generator will place re-usable authorizers
in the root of your JSON API namespace, e.g. `App\JsonApi\DefaultAuthorizer`. Resource specific authorizers
will be placed in the resource's namespace, e.g. `App\JsonApi\Posts\Authorizer`. To avoid confusion, it is
best to give your re-usable authorizers names that do not clash with your resource types.

If your `by-resource` option is set to `false`, re-usable and resource specific authorizers will always be
placed in the `Authorizers` namespace, e.g. `App\JsonApi\Authorizers\DefaultAuthorizer`. This means you
**must not** use names for re-usable authorizers that clash with your resource types.

### Using Authorizers

Authorizers that are not for a specific resource type are registered via middleware. Use the `authorizer`
method to add them to your route middleware.

For example, if you wanted to use the `default` authorizer for your entire API:

```php
JsonApi::register('default')->authorizer('default')->routes(function ($api, $router) {
   // ...
});
```

Or to use the `visitor` authorizer on specific resources:

```php
JsonApi::register('default')->routes(function ($api, $router) {
   $api->resource('posts'); // not protected
   $api->resource('comments')->authorizer('visitor'); // protected
   $api->resource('countries')->authorizer('visitor'); // protected
});
```

> If you call `authorizer` before `middleware`, then the authorizer will run before your other middleware.
If you call it after, it will run after the other middleware. You can call
`$api->middleware('foo')->authorizer('default')->middleware('bar')` to run the authorizer middleware in-between
your other middleware.

Authorizers that are for a specific resource type are automatically detected and invoked, so you do not
need to add them as middleware.

> Authorizers for specific resource types are applied when the `ValidatedRequest` class is resolved from the
service container. This is equivalent to when the `authorize` method on Laravel's
[form request validation](https://laravel.com/docs/validation#form-request-validation) is invoked.

### Writing Authorizers

JSON API requests are mapped to your authorizer methods as follows:

| Request | Authorizer Method |
| :-- | :-- |
| `GET /api/posts` | `index` |
| `POST /api/posts` | `create` |
| `GET /api/posts/1` | `read` |
| `PATCH /api/posts/1` | `update` |
| `DELETE /api/posts/1` | `delete` |
| `GET /api/posts/1/comments` | `readRelationship` |
| `GET /api/posts/1/relationships/comments` | `readRelationship` |
| `POST /api/posts/1/relationships/comments` | `modifyRelationship` |
| `PATCH /api/posts/1/relationships/comments` | `modifyRelationship` |
| `DELETE /api/posts/1/relationships/comments` | `modifyRelationship` |

The `index` and `create` methods receive two function arguments: the domain record class being queried, and
the HTTP request. The domain record class is the fully qualified PHP class name. This means we can authorize
the request as follows:

```php
class DefaultAuthorizer extends AbstractAuthorizer
{

    /**
     * @param string $type
     * @param \Illuminate\Http\Request $request
     */
    public function create($type, $request)
    {
        $this->can('create', $type);
    }
}
```

The `read`, `update` and `delete` methods receive two arguments: the domain record and the HTTP request. For
example:

```php
class DefaultAuthorizer extends AbstractAuthorizer
{

    /**
     * @param object $record
     * @param \Illuminate\Http\Request $request
     */
    public function delete($record, $request)
    {
        $this->can('delete', $record);
    }
}
```

The abstract authorizer already implements that `readRelationship` and `modifyRelationship` methods. It returns
the result of `read` for `readRelationship` and `update` for `modifyRelationship`. You can of course overload these
methods if your logic is different. If you do, these methods receive three arguments: the record that is subject
of the request, the JSON API field name for the relationship and the HTTP request.

```php
class DefaultAuthorizer extends AbstractAuthorizer
{

    /**
     * @param object $record
     * @param string $field
     * @param \Illuminate\Http\Request $request
     */
    public function modifyRelationship($record, $field, $request)
    {
        $this->can('editor', $record);
    }
}
```

## Controller Authorization

If you need to authorize a request *after* the request has been validated, you can do this by using
[controller hooks](./controllers.md). To make this easy, the JSON API controller has the
authorization helper methods described below.

For example, if we wanted to check that a user was authorized to comment on a post, we would need
to know the related post when creating the comment. The request we are expecting from the client
would be as follows:

```http
POST /api/posts HTTP/1.1
Accept: application/vnd.api+json
Content-Type: application/vnd.api+json

{
  "data": {
    "type": "comments",
    "attributes": {
      "content": "..."
    },
    "relationships": {
      "post": {
        "data": {
          "type": "posts",
          "id": "1"
        }
      }
    }
  }
}
```

As our authorization is reliant on being sent a valid `post` relationship, it would be preferable to
run the authorization *after* the JSON API document has been validated, but before the comment is
created. We would use the `creating` hook in our controller:

```php
use CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController;
use CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest;
use App\Post;

class CommentsController extends JsonApiController
{

    protected function creating(ValidatedRequest $request)
    {
        $post = Post::find($request->get("data.relationships.post.data.id"));

        $this->authorize('comment', $post);
    }
}
```

Refer to the [Controllers chapter](./controllers.md) for a full list of the available hooks.

## Available Helpers

The authorizer class and JSON API controller have a number of helper methods to make authentication and
authorization easy.

> If you want to use these helpers somewhere else, apply the
`CloudCreativity\LaravelJsonApi\Auth\AuthorizesRequests` trait to your class.

### `authenticate`

This checks for an authenticated user. If there is no authenticated user, a `Illuminate\Auth\AuthenticationException`
will be thrown, resulting in a `401` response. This will use the `api` guard to check for an authenticated user,
but you can configure the guards to check using the `$guards` property on your authorizer.

For example, the following authorizer will check the `api_v1` guard for an authenticated user:

```php
class DefaultAuthorizer extends AbstractAuthorizer
{

    public $guards = ['api_v1'];

    public function index($type, $request)
    {
        $this->authenticate();
    }
}
```

If you want to just check your application's default guard, set the `$guards` property to an empty array.

### `authorize`

This helper checks whether the authenticated user is authorized to do the action via Laravel's policies. If the
authenticated user does not have the correct authorizations, a `Illuminate\Auth\Access\AuthorizationException`
will be thrown, resulting in a `403` response.

```php
class DefaultAuthorizer extends AbstractAuthorizer
{

    public function update($record, $request)
    {
        $this->authorize('update', $record);
    }
}
```

### `can`

The `can` helper combines the `authenticate` and `authorize` methods by first checking that there is an authenticated
user, then checking if they are authorized for the action. This means this:

```php
class DefaultAuthorizer extends AbstractAuthorizer
{

    public function update($record, $request)
    {
        $this->authenticate();
        $this->authorize('update', $record);
    }
}
```

Can be written as this:

```php
class DefaultAuthorizer extends AbstractAuthorizer
{

    public function update($record, $request)
    {
        $this->can('update', $record);
    }
}
```

If the action is not authorized, either a `401` or `403` response will be sent.
