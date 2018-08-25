# Upgrade Guide 

## Beta Release Cycle

We are now on `1.0.0` beta releases. Changes during this cycle will be kept to the minimum required to
fix remaining issues, most of which relate to validation.

Note that at some point during the beta releases the minimum PHP version will be changed to `7.1`, so
support for Laravel 5.4 will be dropped. We do not plan to refactor existing code to use PHP 7 features,
so this will not result in a major breaking change before `1.0.0`.

## 1.0.0-beta.1 to 1.0.0-beta.2

This upgrade changes some of the internals of the package. You should be able to upgrade without any
changes, unless you are extended and overriding parts of the package.

### Adapters

The `related` method on the resource adapter interface has been renamed `getRelated`. This is so that
the `related` can be used as a JSON API relationship field name. This will not affect your application
unless you have implemented a custom adapter or overridden any of the internals of the Eloquent adapter.

### Relations

In implementing the new `queriesOne` and `queriesMany` relations, we have re-organised some of the internals
of the Eloquent JSON API relation classes. This will not affect your application unless you have extended
any of these classes.

### Validated Request

The protected `isExpectingDocument` method has been removed as it is no longer in use. This will not affect
your application unless you have extended this class.

## 1.0.0-alpha.4 to 1.0.0-beta.1

### Key Names

The default key name used for Eloquent models is now the route key, rather than the database key. I.e. we
now use `Model::getRouteKey`/`Model::getRouteKeyName` rather than `Model::getKey`/`Model::getKeyName`.

This will affect any JSON API resources that relate to Eloquent models where the route key and the database
key are different. It affects both schemas and adapters.

#### Schemas

In your schemas, ensure that your `getId()` method is returning the correct key. You may need to change this:

```php
return (string) $model->geyKey()`;
```

to this:

```php
return (string) $model->getRouteKey();
```

Leaving it as `$model->geyKey()` will maintain the old behaviour, but you will also need to ensure you
have updated your adapter to keep the old behaviour.

> If you are using the deprecated Eloquent schema, you can maintain the old behaviour by setting the
`$idName` property to the key name.

#### Adapters

The Eloquent adapter will now use the the route key name by default. If you need to keep the old
behaviour, set the `$primaryKey` attribute on your adapter to the database key.

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

### Filtering by ID

The Eloquent adapter comes with built-in support for an `id` filter. Previously if a client provided an `id`
filter in a request, this filter was applied and all other filters, sort and paging parameters were ignored.
E.g. previously providing an `id` and `name` filter in the same request would result in only the `id` filter
being applied.

This is now fixed so that if an `id` filter is provided with other `filter`, `page` and `sort` parameters,
the other parameters are also applied. Depending on how your API is used, this could potentially be a
breaking change from the client's perspective.

This may also have subtle effects as follows:

#### Default Filters

If you applied a default filter in your `filter` method regardless of what the client sent, then this will
now also be applied to the `id` filter.

For example, if your `posts` adapter did the following:

```php
protected function filter($query, Collection $filters)
{
    $query->whereNotNull('published_at');
}
```

Previously this would not have been applied if the client sent an `id` filter. I.e. this request:

```http
GET /api/v1/posts?filter['id'][]=1&filter['id'][]=6
```

Would return posts 1 and 6 if they exist, regardless of whether they were published. After upgrading
the same request would return posts 1 and 6 if they exist *and* they are published.

If you need to maintain the old behaviour, the above `filter` method could be modified as follows:

```php
protected function filter($query, Collection $filters)
{
    if ($filters->has('id')) {
        return;
    }

    $query->whereNotNull('published_at');
}
```

However, a better pattern is to let the client be specific about what it is requesting, e.g. write
the `filter` method as follows:

```php
protected function filter($query, Collection $filters)
{
    if ($filters->has('published')) {
        $query->whereNotNull('published_at');
    }
}
```

#### Tests

If you are doing the following in tests:

```php
$this->doSearchById($models)->assertSearchedIds($models);
```

You may find that your tests fail after upgrading if your resource adapter has a default sort order,
as the response will contain the models in a different order. You will need to update your test
so that the asserted models are in an expected order. E.g.:

```php
$expected = $models->sortBy('name');

$this->doSearchById($models)->assertSearchedIds($expected);
```

#### Renamed `findByIds` Method

As part of this change, we renamed the `findByIds` method on the Eloquent adapter to `filterByIds`,
and changed both the method signature and the return type. This will only affect your application if
you overloaded this method.

E.g. change this:

```php
protected function findByIds(Builder $query, Collection $filters)
{
    return $query->where(
        //...
    )->get();
}
```

To this:

```php
protected function filterByIds($query, Collection $filters)
{
    $query->where(
        //...
    );
}
```

## 1.0.0-alpha.* to 1.0.0-alpha.4

View [alpha upgrade notes here.](https://github.com/cloudcreativity/laravel-json-api/blob/v1.0.0-alpha.4/docs/upgrade.md)
