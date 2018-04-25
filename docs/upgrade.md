# Upgrade Guide 

## Alpha Release Cycle

We are now on `1.0.0` alpha releases. We are planning incremental changes during the alpha release cycle that
will involve only small upgrades. We will do one final large upgrade when we switch from alpha to beta releases,
and then we are planning on tagging `1.0.0` after a limited number of beta tags.

## Upgrading to 0.12 to 1.0.0-alpha.1

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

