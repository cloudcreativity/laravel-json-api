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

## Controller

By default, routing will assume that the controller for a resource type is the resource type name suffixed with 
`Controller`. E.g. for a `posts` resource type, the controller will be `PostsController`. The options array to 
the `JsonApi::register` method takes the same options as a route group, so you can set the namespace using the 
`namespace` option.

If you need to override the default name for a resource type's controller, use the `controller` option as follows:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', ['controller' => 'CustomPostsController']);
    $api->resource('comments');
});
```

## Resource Routes

The JSON API spec defines the routes for each resource type. Calling `$api->resource('posts')` will therefore
register the following routes:

| URL | Route Name | Controller Action |
| :-- | :-- | :-- |
| `GET /posts` | `posts.index` | `index` |
| `POST /posts` | `posts.create` | `create` |
| `GET /posts/{record}` | `posts.read` | `read` |
| `PATCH /posts/{record}` | `posts.update` | `update` |
| `DELETE /posts/{record}` | `posts.delete` | `delete` |

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

- **Has One**: The relationship is to zero or one of a related resource. It is represented by a single resource
identifier or `null`.
- **Has Many**: The relationships is to zero to many of a related resource. It always represented by an array of
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

### Has-One Routes

The following has-one routes are registered (using the `author` relationship on the `posts` resource as an example):

| URL | Route Name | Controller Action |
| :-- | :-- | :-- |
| `GET /posts/{record}/author` | `posts.relationships.author` | `readRelatedResource` |
| `GET /posts/{record}/relationships/author` | `posts.relationships.author.read` | `readRelationship` |
| `PATCH /posts/{record}/relationships/author` | `posts.relationships.author.replace` | `replaceRelationship` |

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

### Has-Many Routes

The following has-one routes are registered (using the `comments` relationship on the `posts` resource as an example):

| URL | Route Name | Controller Action |
| :-- | :-- | :-- |
| `GET /posts/{record}/comments` | `posts.relationships.comments` | `readRelatedResource` |
| `GET /posts/{record}/relationships/comments` | `posts.relationships.comments.read` | `readRelationship` |
| `PATCH /posts/{record}/relationships/comments` | `posts.relationships.comments.replace` | `replaceRelationship` |
| `POST /posts/{record}/relationships/comments` | `posts.relationships.comments.add` | `addToRelationship` |
| `DELETE /posts/{record}/relationships/comments` | `posts.relationships.comments.remove` | `removeFromRelationship` |

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
