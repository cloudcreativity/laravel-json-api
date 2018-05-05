# Security

## Introduction

This package provides the ability to [authenticate](https://laravel.com/docs/authentication) and 
[authorize](https://laravel.com/docs/authorization) users to your JSON API in a number of ways:

- Middleware: use Laravel's in-built `auth` middleware to authenticate users to your API.
- Authorizers: classes that contain logic for authorizing and authenticating requests that can be re-used for
multiple JSON API resource types.
- Resource Authorizers: classes that contain logic for authorizing and authenticating requests for a specific
JSON API resource type.
- Controller Authorization: use [controller hooks](./controllers.md) to authorize requests after validation
of a JSON API request.

## Middleware Authentication

Laravel applications come with the `auth` middleware. You can use this to authenticate users to your API.
You can use middleware to protect either the whole API or specific resources.

For example, if your API only returned resources for the authenticated user, you could protect the entire API
using the `auth` middleware as follows:

```php
JsonApi::register('default', ['middleware' => 'auth'], function ($api, $router) {
   // ...
});
```

This will apply the `auth` middleware to every single JSON API route within your API.

If certain resources within your API only ever related to the authenticated user, you can protect the specific
resources as follows:

```php
JsonApi::register('default', [], function ($api, $router) {
   $api->resource('posts'); // not protected
   $api->resource('user-profiles', ['middleware' => 'auth']); // protected
});
```

This will apply the `auth` middleware to every JSON API route for the `user-profiles` resource.

## Authorizers

If you need to run different authentication and authorization logic for the different JSON API resource actions,
then you can define your logic in *authorizer* classes. Authorizers are re-usable across multiple different JSON
API resources.

### Generating an Authorizer

@todo

### Authorizer Helpers

The authorizer class has a number of helper methods to make authentication and authorization easy.

#### `authenticate`

This checks for an authenticated user. If there is no authenticated user, a `Illuminate\Auth\AuthenticationException`
will be thrown, resulting in a `401` response. This will use the default guard to check for an authenticated user,
but you can configure the guards to check using the `$guards` property on your authorizer.

```php
class DefaultAuthorizer extends AbstractAuthorizer
{

    public $guards = ['api'];

    public function index($type, $request)
    {
        $this->authenticate();
    }
}
```

#### `authorize`

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

#### `can`

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
        $this->can('remove', $record);
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

### Using Authorizers

To use an authorizer, you should use the `json-api.auth` middleware. Without any arguments, this will use the
`DefaultAuthorizer`. Using it as `json-api.auth:visitor` would use the `VisitorAuthorizer`.

For example, if you wanted to use the default authorizer for your entire API:

```php
JsonApi::register('default', ['middleware' => 'json-api.auth'], function ($api, $router) {
   // ...
});
```

Or to use the visitor authorizer on specific resources:

```php
JsonApi::register('default', [], function ($api, $router) {
   $api->resource('posts'); // not protected
   $api->resource('comments', ['middleware' => 'json-api.auth:visitor']); // protected
   $api->resource('countries', ['middleware' => 'json-api.auth:visitor']); // protected
});
```

## Resource Authorizers

@todo

## Controller Authorization
