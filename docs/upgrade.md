# Upgrade Guide 

## Alpha Release Cycle

We are now on `1.0.0` alpha releases. We are planning incremental changes during the alpha release cycle that
will involve only small upgrades. We will do one final large upgrade when we switch from alpha to beta releases,
and then we are planning on tagging `1.0.0` after a limited number of beta tags.

## 1.0.0-alpha.3 to 1.0.0-beta.1

### Controllers

If you have overloaded the `read` method on any of your controllers, you will need to change the 
method signature. Change this:

```php
/**
 * @param CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest $request
 */
public function read(ValidatedRequest $request)
{
    // ...
}
```

To this:

```php
/**
 * @param CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface
 * @param CloudCreativity\LaravelJsonApi\Http\Requests\ValidatedRequest $request
 */
public function read(StoreInterface $store, ValidatedRequest $request)
{
    // ...
}
```

### Adapters

The `store` method has been renamed to `getStore` to avoid collisions with JSON API relation names. This change
will only affect applications that are overloading internal adapter methods.

### Has-Many

The method signatures for the `sync` and `detach` methods in the JSON API Eloquent `HasMany` relation have been
changed to fix a bug with hydrating morph-many relations. This will only affect your application if you have
extended that class and overloaded either of these methods. For both methods, the type-hinting of the first
method argument has been removed.

## 1.0.0-alpha.2 to 1.0.0-alpha.3

### Exception Handler

The `isJsonApi()` method on the `HandlesError` trait now requires the request and exception as arguments.
This is so that a JSON API error response can be rendered if a client has requested JSON API via the request
`Accept` header, but the request is not being processed by one of the configured APIs. This enables exceptions
that are thrown *prior* to routing to be rendered as JSON API - for example, when the application is in
maintenance mode.

You need to change this:

```php
public function render($request, Exception $e)
{
  if ($this->isJsonApi()) {
    return $this->renderJsonApi($request, $e);
  }

  // ...
}
```

To this:

```php
public function render($request, Exception $e)
{
  if ($this->isJsonApi($request, $e)) {
    return $this->renderJsonApi($request, $e);
  }

  // ...
}
```

### Default API

You can now [set the default API name used by this package.](./basics/api.md) If your default API is not 
called `default`, you must set this to whatever your default API is called.

### Not By Resource Resolution

When using *not-by-resource* resolution, the type of the class is now appended to the class name. E.g. 
`App\JsonApi\Adapters\PostAdapter` is now expected instead of `App\JsonApi\Adapters\Post`. The previous
behaviour can be maintained by setting the `by-resource` config option to the string `false-0.x`, i.e.

```php
return [
    'by-resource' => 'false-0.x',
    
    // ...
];
```

We will support this legacy behaviour throughout  `1.0` releases and it will be removed in `2.0`, giving
you plenty of time to rename your classes. See 
[this issue](https://github.com/cloudcreativity/laravel-json-api/issues/176)
for why we made this change.

### Eloquent Adapters

Eloquent `hasManyThrough` relations were previously defined on the Eloquent adapter using the `hasMany` method.
You now need to use the `hasManyThrough` method instead. This change **only** affects Eloquent `hasManyThrough`
relations, i.e. you *do not* need to make changes for Eloquent `hasMany`, `belongsToMany`, `morphMany` and
`morphToMany` relations.

Change this:

```php
protected function posts()
{
    return $this->hasMany();
}
```

To this:

```php
protected function posts()
{
    return $this->hasManyThrough();
}
```

### Generic Adapters

We have pulled some of the logic from our Eloquent adapter that was not actually specific to Eloquent out of
that adapter and placed it in the `AbstractResourceAdapter`. This may affect your implementation if there
is a collision with the methods or traits that we have added.

One change is the `hydrateRelationships` method is no longer abstract. You can remove this method from your
adapter if it had no code in it.

## 1.0.0-alpha.1 to 1.0.0-alpha.2

### Controllers

Controller hooks now receive the `ValidatedRequest` instance instead of the resource object submitted by the
client. This will affect your application if you were using this argument in any hooks, or if overloaded some of
the `protected` methods in the `JsonApiController`. It will not affect your application if you did not type-hint
this argument in any of the hooks, or overload any protected methods.

Refer to the [updated Controllers chapter](./basics/controllers.md) for examples.

### Authorizers

The old authorizer implementation has been removed to replace it with a new Laravel-friendly implementation.
This is fully documented in the [Security chapter](./basics/security.md) - so to upgrade we suggest you check
out that documentation.

## Upgrading from 0.12 to 1.0.0-alpha.1

The main new feature introduced in this release is proper handling of reading and modifying resource
relationships. We have also worked our way through a number of the issues on the 1.0.0 milestone.

Use the following commands:

```bash
$ composer require cloudcreativity/laravel-json-api:1.0.0-alpha.1
$ composer require --dev cloudcreativity/json-api-testing:^0.4
```

### Namespaces

As we are now only developing JSON API within Laravel applications, we have deprecated our framework agnostic
`cloudcreativity/json-api` package. All the classes from that package have been merged into this package and
renamed to the `CloudCreativity\LaravelJsonApi` namespace. This will allow us to more rapidly develop this
Laravel package and simplify the code in subsequent releases.

Use the search/replace feature of your code editor to replace all occurrences of `CloudCreativity\JsonApi` with
`CloudCreativity\LaravelJsonApi`.

Once you have done this, run the following command to remove the deprecated package:

```bash
$ composer remove cloudcreativity/json-api
```

The following trait has also moved to a different namespace:

- `Hydrator\HydratesAttributesTrait` moved to `Adapter\HydratesAttributesTrait`

### Routing

Controllers are now optional by default. If no controller option is provided when registering a resource,
the `JsonApiController` from this package will be used.

To use the previous behaviour (whereby the controller name is generated using the resource name), pass
`true` as the controller option:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', ['controller' => true]);
});
```

As per previous versions, the `controller` option can also be a string controller name. Refer to the
[Controllers documentation](./basics/controllers.md) for more details.

### Controllers

The `EloquentController` no longer has any constructor dependencies. Previously you were injecting a model
and optionally a hydrator. These must be removed. Note that the Eloquent Controller has been deprecated as it
now does not have any unique code - you can extend `JsonApiController` directly.

If you were overloading any of the methods in either `EloquentController` or `JsonApiController`, you may find
that some of the method signatures have been modified. Refer to the `JsonApiController` for the new signatures.

Note that we have now implemented full support for relationships, and the updated `JsonApiController` will
handle these automatically. If you had a custom implementation for relationship endpoints, you will need to
refer to the documentation on relationships.

### Hydrators

Hydrators have been merged into the Adapter classes. This simplifies things by making a single class that is
responsible for reading and writing resources to/from your application's storage.

> We suggest taking a look at the newly added [adapters documentation](./basics/adapters.md).

If you have any non-Eloquent adapters, you will need to implement the new methods on the adapter interface. We
suggest you check out the documentation on Adapters for guidance.

For Eloquent hydrators, transfer any properties and code from you hydrator into your adapter class. Then make the 
following modifications...

The `$attributes` property now only needs to list JSON API resource attributes that are mapped to a different
name on the model. All other resource attributes are automatically transferred to the snake case or camel case
equivalent and filled into your model.

For example, if you previously had this on your hydrator:

```php
$attributes = [
    'title',
    'slug',
    'published-at' => 'published_date',
];
```

You would only need the attributes to now be:

```php
$attributes = [
    'published-at' => 'published_date',
];
```

If you need to prevent JSON API fields from being transferred to your model, add them to the `$guarded` 
or `$fillable` attributes on your adapter. Refer to the [mass assignment](./basics/adapters.md)
section in the adapters chapter.

Any relationships that you are listing in the `$relationships` property will now need a relationship method
implemented. Refer to the
[adapter relationship documentation](./basics/adapters.md#Relationships)
as this is a new feature. As an example, if you had this on your hydrator:

```php
protected $relationships = ['author'];
```

You would need to add the following method to your adapter:

```php
protected function author()
{
    return $this->belongsTo();
}
```

### Eloquent Adapters

Several methods have had their type-hinting of an Eloquent query builder removed, as the method may now also
receive an Eloquent relation. This affects your `filter` method, and may affect other methods you may have
overloaded. The change is as follows:

```php
protected function filter(Builder $query, Collection $filters) {}
```

becomes this:

```php
protected function filter($query, Collection $filters) {}
```

Adapters now support reading and writing relationships. Refer to the
[adapters documentation](./basics/adapters.md) on using this new feature.

### Eloquent Schemas

There have been some internal changes to the Eloquent schema. The main one that may affect your schemas is
that the default attributes to serialize are now those returned by `$model->getVisible()`. Previously
`$model->getFillable()` was used.

> We will mention now that **we plan to deprecate Eloquent schemas during the alpha release cycle.** If making
these changes will take a while, we recommend that you spend the time converting your Eloquent
schemas to generic schemas.

Schemas are now documented, so refer to the [Schemas chapter](./basics/schemas.md) for more information.

