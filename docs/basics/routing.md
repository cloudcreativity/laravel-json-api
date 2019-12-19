# Routing

## Api Routing

To define the routes available in an API, register the API in your `routes/api.php` file as follows:

```php
JsonApi::register('default')->routes(function ($api) {
    $api->resource('posts');
    $api->resource('comments');
});
```
> If you prefer not to use the facade, use `app("json-api")->register()` instead.

The `register` method takes the name of the JSON API. This must match the name used for the API's config.
(So the above example uses `config/json-api-default.php`.) The closure that is passed to the `routes` method
is where you define your API's routes. The `$api` argument passed to it is a resource registrar that provides
methods to allow you to easily define your API's routes.

> The `routes` closure is executed within a Laravel route group that has this package's JSON API features added
to it.

### API Route Prefix

When registering a JSON API, we automatically read the URL prefix and route name prefix from your 
[API's URL configuration](./api#url) and apply this to the route group for your API. The URL prefix in your JSON API 
config is **always** relative to the root URL on a host, i.e. from `/`.
**This means when registering your routes, you need to ensure that no prefix has already been applied.**

> The default Laravel installation has an `api` prefix for API routes. Refer back to the
[Installation guide](../installation.md) for instructions on how to modify it for use with JSON API.

### API Controller Namespace

You can set the API controller namespace for your API using the `withNamespace()` method. For example:

```php
JsonApi::register('default')->withNamespace('Api')->routes(function ($api) {
    $api->resource('posts');
    $api->resource('comments');
});
```

> We use `withNamespace()` instead of Laravel's usual `namespace()` method because `namespace` is a
[Reserved Keyword](http://php.net/manual/en/reserved.keywords.php). 

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

To register only some of these routes, use the `only`, `except` or `readOnly` methods as follows:

```php
JsonApi::register('default')->routes(function ($api) {
    $api->resource('posts')->only('index', 'read');
    $api->resource('comments')->except('update', 'delete');
    $api->resource('users')->readOnly(); // this is a shorthand for: only('index', 'read');
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
JsonApi::register('default')->routes(function ($api) {
    $api->resource('posts')->relationships(function ($relations) {
        $relations->hasOne('author');
        $relations->hasMany('comments');
    });
});
```

### Related Resource Type

When registering relationship routes, it is assumed that the resource type returned in the response is the
pluralised form of the relationship name. For example, the above example assumes that the post's author route
will return an `authors` JSON API resource.

If this is not the case, then you must specify the inverse type. For example, if the `author` relationship
returned a `users` resource:

```php
JsonApi::register('default')->routes(function ($api) {
    $api->resource('posts')->relationships(function ($relations) {
        $relations->hasOne('author', 'users');
        $relations->hasMany('comments');
    });
});
```

> The related resource type needs to be known so that the query parameters for requests to relationship
routes can be validated correctly. For example, for a *to-many* relationship, the filters that are allowed
will be those allowed for the related resource type. So a request to `posts/1/author` will need to validate
the filters for those allowed for `users` resources, not the `posts` resource.

### To-One Routes

The following to-one routes are registered (using the `author` relationship on the `posts` resource as an example):

| URL | Route Name |
| :-- | :-- |
| `GET /posts/{record}/author` | `posts.relationships.author` |
| `GET /posts/{record}/relationships/author` | `posts.relationships.author.read` |
| `PATCH /posts/{record}/relationships/author` | `posts.relationships.author.replace` |

To register only some of these, use the `only` or `except` methods. E.g.

```php
JsonApi::register('default')->routes(function ($api) {
    $api->resource('posts')->relationships(function ($relations) {
        $relations->hasOne('author')->only('related', 'read');
        $relations->hasOne('site')->except('replace');
    });
});
```

In the above example, both to-one relationships are effectively read-only, so the example could be re-written
as:

```php
JsonApi::register('default')->routes(function ($api) {
    $api->resource('posts')->relationships(function ($relations) {
        $relations->hasOne('author')->readOnly();
        $relations->hasOne('site')->readOnly();
    });
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

To register only some of these, use the `only`, `except` or `readOnly` methods:

```php
JsonApi::register('default')->routes(function ($api, $router) {
    $api->resource('posts')->relationships(function ($relations) {
        $relations->hasMany('comments')->only('related', 'read'); // same as `readOnly()`.
        $relations->hasMany('likes')->except('replace', 'remove');
    });
});
```

## Id Constraints

To constrain the `{record}` route parameter for a specific resource, use the `id` method as follows:

```php
JsonApi::register('default')->routes(function ($api, $router) {
    $api->resource('posts')->id('[\d]+');
});
```

To apply an id constraint to every resource in your API, use the `defaultId` method on the API as follows:

```php
JsonApi::register('default')->defaultId('[\d]+')->routes(function ($api, $router) {
    $api->resource('posts');
});
```

If using a default constraint for the API, you can override it for a specific resource. For example:

```php
JsonApi::register('default')->defaultId('[\d]+')->routes(function ($api, $router) {
    $api->resource('posts'); // has the default constraint
    $api->resource('comments')->id('[A-Z]+'); // has its own constraint
    $api->resource('tags')->id(null); // has no constaint
});
```

## Middleware

### API Middleware

When you register a JSON API, we add the `json-api` middleware. This boots the JSON API features
provided by this package for handling an inbound request.

If you need to run middleware *before* this JSON API middleware runs, wrap your JSON API registration in
a group as follows:

```php
Route::group(['middleware' => 'my_middleware'], function () {
  JsonApi::register('default')->routes(function ($api) {
     // ...
  });

  // other routes
});
```

If you need to run middleware *after* this JSON API middleware, and across your entire API, you can use the
`middleware` method. For example, if we wanted one throttle rate across the entire API:

```php
JsonApi::register('default')->middleware('throttle:60,1')->routes(function ($api) {
    // ...
});
```

### Resource Middleware

If you need to register middleware that runs only for a specific resource type, use the `middleware` method
when registering the resource. For example if we wanted different throttle rates per resource type:

```php
JsonApi::register('default')->routes(function ($api) {
    $api->resource('posts')->middleware('throttle:30,1');
    $api->resource('comments')->middleware('throttle:60,1');
});
```

> This middleware will run for every request for the resource type, including its relationships.

## Controllers

By default no controller is required because this package contains a standard controller for processing JSON API
requests. However it is possible to specify that a resource has its own controller, using the `controller` method.

For example, the following would use the `PostsController` in the `Api` namespace:

```php
JsonApi::register('default')->withNamespace('Api')->routes(function ($api, $router) {
    $api->resource('posts')->controller(); // uses PostsController
});
```

### Controller Names

If you call `controller()` without any arguments, we assume your controller is the camel case name version of 
the resource type with `Controller` on the end. I.e. `posts` would expect `PostsController` and
`blog-posts` would expect `BlogPostsController`. Or if your resource type was `post`,
we would guess `PostController`.

If your resource names are plural, e.g. `posts`, but you would like to use the singular for the controller
name, i.e. `PostController`, use the `singularControllers()` method as follows:

```php
JsonApi::register('default')
    ->withNamespace('Api')
    ->singularControllers()
    ->routes(function ($api, $router) {
        $api->resource('posts')->controller(); // uses PostController
    });
```

If your controller names do not conform to either of these patterns, you have two options. Either explicitly
provide the controller name for each resource, e.g.:

```php
JsonApi::register('default')->withNamespace('Api')->routes(function ($api, $router) {
    $api->resource('posts')->controller('PostResourceController');
});
```

Or you can provide a callback to work it out from the resource name:

```php
JsonApi::register('default')->withNamespace('Api')->controllerResolver(function ($resourceType) {
    return ucfirst($resourceType) . 'ResourceController';
})->routes(function ($api, $router) {
    $api->resource('posts')->controller(); // expects PostsResourceController
});
```

### Default Controller

If you do not specify the controller for a resource type we use our own controller, which is:
`CloudCreativity\LaravelJsonApi\Http\Controllers\JsonApiController`.

If you want to override this default, use the `defaultController` method on the API. For example, if
you had extended our controller in your `Api` namespace:

```php
JsonApi::register('default')
    ->withNamespace('Api')
    ->defaultController('DefaultController')
    ->routes(function ($api, $router) {
        $api->resource('posts'); // uses DefaultController instead of our JsonApiController
    });
```

If you want to use a controller in a completely different namespace, Laravel allows you to reset the
namespace on a controller name. This example shows our API having the `Api` namespace, but our
controller being in a completely different namespace:

```php
use Foo\Bar\DefaultController;

JsonApi::register('default')
    ->withNamespace('Api')
    ->defaultController('\\' . DefaultController::class)
    ->routes(function ($api, $router) {
        $api->resource('posts')->controller();
    });
```

For more information on controllers, see the [Controllers chapter](./controllers.md).

## Custom Routes

We also support adding routes to your API that are not defined by the JSON API specification. You can
either add these at the root of your API, or within a resource.

If you are using these, you will also need to refer to the *Custom Actions* section in the
[Controllers chapter](./controllers.md).

Also note that custom routes are registered *before* the routes defined by the JSON API specification,
i.e. those that are added when you call `$api->resource('posts')`. You will need to ensure that your
custom route definitions do not collide with these defined routes. 

> Generally we advise against registering custom routes. This is because the JSON API specification may
have additional routes added to it in the future, which might collide with your custom routes.

### API Custom Routes

If we wanted an index route for our API that returns the version of the API, we can add this as follows:

```php
JsonApi::register('default')->withNamespace('api')->routes(function ($api) {
    $api->get('/', 'HomeController@version');
});
```

You can use any method chaining that Laravel allows when registering this route. For example:

```php
JsonApi::register('default')->withNamespace('api')->routes(function ($api) {
    $api->middleware('throttle:10,1')->get('/', 'HomeController@version');
});
```

### Resource Custom Routes

To add custom routes for a specific resource type:

```php
JsonApi::register('default')->withNamespace('Api')->routes(function ($api) {
    $api->resource('posts')->controller()->routes(function ($posts) {
        // e.g. DELETE /api/posts
        $posts->delete('/');
        // e.g. POST /api/posts/123/share where 123 is the post id.
        $posts->post('{record}/share', 'share');
    });

    $api->resource('comments')->controller()->routes(function ($comments) {
        // e.g. POST /api/comments/123/post/share where 123 is the comment id.
        $comments->field('post')->post('{record}/post/share', 'sharePost');
    });
});
```

**Note that you must use `{record}` as the route parameter for the resource id.**

> The `routes` callback is executed within the Laravel route group for the specified resource type.

Normal Laravel fluent routing methods are supported, for example we could call `middleware` as follows:

```php
JsonApi::register('default')->withNamespace('Api')->routes(function ($api) {
    $api->resource('posts')->controller()->routes(function ($posts) {
        $posts->middleware('auth')->post('{record}/share', 'share');
    });
});
```

There are just two differences to note. Firstly, when supplying the controller action string, you do not
need to specify a controller. E.g. the example above uses `share` instead of the usual `PostsController@share`.
This is because we know the controller that you are using for your resource type, so if your action string
does not contain an `@` symbol we add the controller name to it.

Secondly, if you are defining a custom relationship route, you must use the `field` method. This takes
the relationship name as its first argument. The inverse resource type can be specified as the second argument,
for example: 

```php
JsonApi::register('default')->withNamespace('Api')->routes(function ($api) {
    $api->resource('comments')->controller()->routes(function ($comments) {
        // Inverse resource type is `blog-posts` not `posts`:
        $comments->field('post', 'blog-posts')->post('{record}/post/share', 'sharePost');
    });
});
```
