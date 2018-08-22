# Routing

## Api Routing

To define the routes available in an API, register the API in your `routes/api.php` file as follows:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts');
    $api->resource('comments');
});
```
> If you are not using the `JsonApi` facade, use `app("json-api")->register()` instead.

This is similar to registering a Laravel route group, except the first argument is the name of your API that must
match the name used for the API's config. (So the above example uses `config/json-api-default.php`.) The other 
difference is that the `Closure` receives an API object as its first argument (and the Laravel router as its second).
This API object is a helper object for registering JSON API resources.

When registering a JSON API, we automatically read the URL prefix and route name prefix from your 
[API's URL configuration](./api#url) and apply this to the route group for your API. The URL prefix in your JSON API 
config is **always** relative to the root URL on a host, i.e. from `/`. This means when registering your routes, 
you need to ensure that no prefix has already been applied.

>  The default Laravel installation has an `api` prefix for API routes. If you are registering a JSON API in your
`routes/api.php` file, you will need to remove the prefix from the `mapApiRoutes()` method in your 
`RouteServiceProvider`.

## Resource Routes

The JSON API spec defines the routes for each resource type. Calling `$api->resource('posts')` will therefore
register the following routes:

| URL | Route Name |
| :-- | :-- |
| `GET /posts` | `posts.index` |
| `POST /posts` | `posts.create` |
| `GET /posts/{record}` | `posts.read` |
| `PATCH /posts/{record}` | `posts.update` |
| `DELETE /posts/{record}` | `posts.delete` |

To register only some of these routes, use the `only` or `except` options as follows:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', [
        'only' => ['index', 'read']
    ]);
    $api->resource('comments', [
        'except' => ['update', 'delete']
    ]);
});
```

## Relationship Routes

The JSON API spec also defines routes for relationships on a resource type. There are two types of relationships:

- **To One**: The relationship is to zero or one of a related resource. It is represented by a single resource
identifier or `null`.
- **To Many**: The relationships is to zero to many of a related resource. It always represented by an array of
resource identifiers (and the array may be empty).

Relationship routes can be registered as follows:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', [
        'has-one' => 'author',
        'has-many' => ['comments', 'likes'],
    ]);
    $api->resource('comments', [
        'has-one' => ['post', 'author'],
        'has-many' => 'likes',
    ]);
});
```

> Note that you can use a string for a single relationship, or an array of string for multiple.

### Related Resource Type

When registering relationship routes, it is assumed that the resource type returned by the route is the
pluralised relationship name. For example, the above example assumes that the post's author route will
return an `authors` JSON API resource.

If this is not the case, then you can specify the related resource type using the `inverse` setting. For
example, if the post's author route returned a `users` resource:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', [
        'has-one' => [
            'author' => ['inverse' => 'users'],
        ],
    ]);
});
```

> The related resource type needs to be known so that the query parameters for requests to relationship
routes can be validated correctly. For example, for a *to-many* relationship, the filters that are allowed
will be those allowed for the related resource type. So a request to `posts/1/tags` will need to validate
the filters for those allowed for `tags` resources, not the `posts` resource.

### To-One Routes

The following to-one routes are registered (using the `author` relationship on the `posts` resource as an example):

| URL | Route Name |
| :-- | :-- |
| `GET /posts/{record}/author` | `posts.relationships.author` |
| `GET /posts/{record}/relationships/author` | `posts.relationships.author.read` |
| `PATCH /posts/{record}/relationships/author` | `posts.relationships.author.replace` |

To register only some of these, use the `only` or `except` options with the relationship. E.g.

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', [
        'has-one' => [
            'author' => ['only' => ['related', 'read']],
            'site' => ['except' => 'replace'],
        ],
    ]);
});
```

### To-Many Routes

The following to-many routes are registered (using the `comments` relationship on the `posts` resource as an example):

| URL | Route Name |
| :-- | :-- |
| `GET /posts/{record}/comments` | `posts.relationships.comments` |
| `GET /posts/{record}/relationships/comments` | `posts.relationships.comments.read` |
| `PATCH /posts/{record}/relationships/comments` | `posts.relationships.comments.replace` |
| `POST /posts/{record}/relationships/comments` | `posts.relationships.comments.add` |
| `DELETE /posts/{record}/relationships/comments` | `posts.relationships.comments.remove` |

To register only some of these, use the `only` or `except` options with the relationship. E.g.

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', [
        'has-many' => [
            'comments' => ['only' => ['related', 'read'],
            'likes' => ['except' => 'replace'],
        ],
    ]);
});
```

## Id Constraints

To constrain the `{record}` route parameter for a specific resource, use the `id` option as follows:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', ['id' => '[\d]+']);
});
```

To apply an id constraint to every resource in your API, use the `id` option when registering the API as follows:

```php
JsonApi::register('default', ['namespace' => 'Api', 'id' => '[\d]+'], function ($api, $router) {
    $api->resource('posts');
});
```

If using a constraint for the API, you can override it for a specific resource. For example:

```php
JsonApi::register('default', ['namespace' => 'Api', 'id' => '[\d]+'], function ($api, $router) {
    $api->resource('posts'); // has the default constraint
    $api->resource('comments', ['id' => '[A-Z]+']); // has its own constraint
    $api->resource('tags', ['id' => null]); // has no constaint
});
```

## Middleware

### API Middleware

When you register a JSON API, two pieces of middleware are configured for the entire API:

- `json-api`: This boots JSON API support for the inbound request, including running content negotiation to
ensure that the request is a JSON API request and that the client will accept a JSON API response.
- `json-api.bindings`: This replaces the `{record}` route parameter with the PHP object that it relates to,
or sends a `404` response if the resource id is not recognised.

If you need to run middleware *before* this JSON API middleware runs, wrap your JSON API registration in
a group as follows:

```php
Route::group(['middleware' => 'my_middleware'], function () {
  JsonApi::register('default', [], function ($api, $router) {
     // ...
  });

  // other routes
});
```

If you need to run middleware *after* this JSON API middleware, and across your entire API, you can do so using
the options when registering the API. For example, if we wanted one throttle rate across the entire API:

```php
JsonApi::register('default', ['middleware' => 'throttle:60,1'], function ($api, $router) {
   // ...
});
```

### Resource Middleware

If you need to register middleware that runs only for a specific resource type, use the `middleware` option
when registering the resource. For example if we wanted different throttle rates per resource type:

```php
JsonApi::register('default', [], function ($api, $router) {
    $api->resource('posts', ['middleware' => 'throttle:30,1']);
    $api->resource('comments', ['middleware' => 'throttle:60,1']);
});
```

> This middleware will run for every request for the resource type, including its relationships.

## Controllers

By default no controller is required because this package contains a standard controller for processing JSON API
requests. However it is possible to specify your own controller, using the `controller` option.

For example, the following would use the `PostsController` in the `Api` namespace:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', ['controller' => 'PostsController']);
});
```

For more information on controllers, see the [Controllers chapter](./controllers.md).
