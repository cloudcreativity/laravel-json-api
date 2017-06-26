# Routing

## Api Routing

To define the routes available in an API, register the API in your `routes/api.php` file as follows:

```php
JsonApi::api('default', ['as' => 'api.'], function ($api, $router) {
    $api->resource('posts');
    $api->resource('comments');
});
```

This is similar to registering a Laravel route group, except the first argument is the name of your API that must
match the name used for the API's config. (So the above example uses `config/json-api-default.php`.) The other 
difference is that the `Closure` receives an API object as its first argument (and the Laravel router as its second).
This API object is a helper object for registering JSON API resources.

> If you are not using the `JsonApi` facade, resolve `CloudCreativity\LaravelJsonApi\Routing\ResourceRegistrar` from
the service container instead.

## Controller

By default, routing will assume that the controller for a resource type is the resource type name suffixed with `Controller`. E.g. for a `posts` resource type, the controller will be `PostsController`. The options array to the `JsonApi::api` method takes the same options as a route group, so you can set the namespace using the `namespace` option.

If you need to override the default name for a resource type's controller, use the `controller` option as follows:

```php
JsonApi::api('default', ['as' => 'api.', 'namespace' => 'My\Api'], function ($api, $router) {
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
| `GET /posts/{resource_id}` | `posts.read` | `read` |
| `PATCH /posts/{resource_id}` | `posts.update` | `update` |
| `DELETE /posts/{resource_id}` | `posts.delete` | `delete` |

To register only some of these routes, use the `only` or `except` options as follows:

```php
JsonApi::api('default', ['as' => 'api.'], function ($api, $router) {
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
JsonApi::api('default', ['as' => 'api.'], function ($api, $router) {
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
| `GET /posts/{resource_id}/author` | `posts.relationships.author` | `readRelatedResource` |
| `GET /posts/{resource_id}/relationships/author` | `posts.relationships.author.read` | `readRelationship` |
| `PATCH /posts/{resource_id}/relationships/author` | `posts.relationships.author.replace` | `replaceRelationship` |

To register only some of these, use the `only` or `except` options with the relationship. E.g.

```php
JsonApi::api('default', ['as' => 'api.'], function ($api, $router) {
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
| `GET /posts/{resource_id}/comments` | `posts.relationships.comments` | `readRelatedResource` |
| `GET /posts/{resource_id}/relationships/comments` | `posts.relationships.comments.read` | `readRelationship` |
| `PATCH /posts/{resource_id}/relationships/comments` | `posts.relationships.comments.replace` | `replaceRelationship` |
| `POST /posts/{resource_id}/relationships/comments` | `posts.relationships.comments.add` | `addToRelationship` |
| `DELETE /posts/{resource_id}/relationships/comments` | `posts.relationships.comments.remove` | `removeFromRelationship` |

To register only some of these, use the `only` or `except` options with the relationship. E.g.

```php
JsonApi::api('default', ['as' => 'api.'], function ($api, $router) {
    $api->resource('posts', [
        'has-many' => [
            'comments' => ['only' => ['related', 'read'],
            'likes' => ['except' => 'replace'],
        ],
    ]);
});
```

## Id Constraints

To constrain the `{resource_id}` route parameter for a specific resource, use the `id` option as follows:

```php
JsonApi::api('default', ['as' => 'api.'], function ($api, $router) {
    $api->resource('posts', ['id' => '[\d]+');
});
```
