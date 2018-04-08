# Upgrade Guide 

As we are currently on pre-1.0 releases, when you upgrade you will also need to specify that package
dependencies need to be upgraded. Use the following commands:

```bash
$ composer require cloudcreativity/laravel-json-api:1.0.0-alpha.1
$ composer require --dev cloudcreativity/json-api-testing:^0.4
```

## Upgrading to 0.12 to 1.0.0-alpha.1

The main new feature introduced in this release is proper handling of reading and modifying resource
relationships. We have also worked our way through a number of the issues on the 1.0.0 milestone.

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

The following have also moved to different namespaces and again a search/replace will help fix any references:

- `Hydrator\HydratesAttributesTrait` moved to `Adapter\HydratesAttributesTrait`

### Hydrators

Hydrators have been merged into the Adapter classes. This simplifies things by making a single class that is
responsible for reading and writing resources to/from your application's storage.

If you have any non-Eloquent adapters, you will need to implement the new methods on the adapter interface. We
suggest you check out the documentation on Adapters for guidance.

For Eloquent hydrators, transfer any properties and code from you hydrator into your adapter class. Then make the 
following modifications...

The `$attributes` property now only needs to list JSON API resource attributes that are mapped to a different
name on the model. All other resource attributes are automatically transferred to the snake case or camel case
equivalent and filled into your model. This means that the attributes need to be mass-assignable.

For example, if you previouly had this on your hydrator:

```php
$attributes = [
    'title',
    'slug',
    'published-at' => 'published-date',
];
```

You would only need the attributes to now be:

```php
$attributes = [
    'published-at' => 'published-date',
];
```

Any relationships that you are listing in the `$relationships` property will now need a relationship method
implemented. Refer to the relationship documentation as this is a new feature.

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

Adapters now support reading and writing relationships. Refer to the documentation on using this new feature.

### Controllers

The `EloquentController` no longer has any constructor dependencies. Previously you were injecting a model
and optionally a hydrator. These can be removed. Note that the Eloquent Controller has been deprecated as it
now does not have any unique code - you can extend `JsonApiController` directly.

If you were overloading any of the methods in either `EloquentController` or `JsonApiController`, you may find
that some of the method signatures have been modified. Refer to the `JsonApiController` for the new signatures.

Note that we have now implemented fully support for relationships, and the updated `JsonApiController` will 
handle these automatically. If you had a custom implementation for relationship endpoints, you will need to
refer to the documentation on relationships.
