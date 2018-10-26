# Upgrade Guide 

## Beta Release Cycle

We are now on `1.0.0` beta releases. Changes during this cycle will be kept to the minimum required to
fix remaining issues, most of which relate to validation.

## 1.0.0-beta.5 to 1.0.0-beta.6

### Adapters

We have modified the adapter interface to ensure that Laravel's transformation middleware (e.g. trim strings) 
work with this package. This means that adapters now receives the JSON API document (the HTTP request body) as
an array. This has also resulted in some changes to the method signatures of methods on our abstract adapters.

An advantage is it has enabled us to deprecated the document object interface/classes
(in the `Contracts\Object` and `Object` namespaces). These were in this package for historic reasons and we 
have wanted to remove them for some time. They are marked as deprecated and will be removed for good in `2.0.0`.

Below are the main changes if you have extended our abstract adapters. Your adapters may not have all
of these methods.

> If you have overridden any of the internals of the abstract adapters you may need to make additional changes.
If you have directly implemented the interface to write your own adapter, you will need to refer to the updated
adapter interface for changes.

#### `createRecord()`

The method signature has changed from:

`createRecord(\CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface $resource)`

to:

`createRecord(\CloudCreativity\LaravelJsonApi\Document\ResourceObject $resource)`

The new resource object class represents the resource sent by the client. Values can be accessed using the
JSON API field name (i.e. the attribute or relationship name). For example `$value = $resource['title']` 
would access the `title` attribute. This is far simpler than the previous resource object class.

#### `fillAttributes()`

The method signature has changed from:

`fillAttributes($record, \CloudCreativity\Utils\Object\StandardObjectInterface $attributes)`

to:

`fillAttributes($record, \Illuminate\Support\Collection $attributes)`

#### `hydrateRelated`

This method has been renamed `fillRelated` and the method signature has changed from:

```
hydrateRelated(
  $record, 
  \CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface $resource,
  \Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface
)`
```

to:

```
fillRelated(
  $record, 
  \CloudCreativity\LaravelJsonApi\Document\ResourceObject $resource,
  \Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface
)`
```

## 1.0.0-beta.3 to 1.0.0-beta.5

> You should upgrade directly to `beta.5` as `beta.4` had a
[bug](https://github.com/cloudcreativity/laravel-json-api/issues/240) in it.

The minimum PHP version is now `7.1` and the minimum Laravel version is `5.5`.

### Validation

This release adds in the new validation implementation. The previous implementation remains and
will be removed at `2.0`. This means you do not have to make any changes to your existing validators.
However, you should note that the previous implementation is considered end-of-life and will not
receive any fixes.

To upgrade existing validators to the new ones, check out the [validation docs](./basics/validators.md).
Note that if you run a generator, the validators class created will be the new implementation.

### Controllers

We have made the `ValidatedRequest` class abstract and created a class for all the different
requests the controller handles. This will only affect your application if you have overloaded
any of the following actions (methods) on a controller. You will need to change the
type-hint from `ValidatedRequest` to the class shown in the following table.

> All request classes are in the `CloudCreativity\LaravelJsonApi\Http\Requests` namespace.

| Action | Request Class |
| :-- | :-- |
| `index` | `FetchResources` |
| `create` | `CreateResource` |
| `read` | `FetchResource` |
| `update` | `UpdateResource` |
| `delete` | `DeleteResource` |
| `readRelatedResource` | `FetchRelated` |
| `readRelationship` | `FetchRelationship` |
| `replaceRelationship` | `UpdateRelationship` |
| `addToRelationship` | `UpdateRelationship` |
| `removeFromRelationship` | `UpdateRelationship` |

> This change **does not** affect any of the protected methods on the controller that type-hint
`ValidatedRequest`, as all of the new request classes extend `ValidatedRequest`.

## 1.0.0-beta.2 to 1.0.0-beta.3

With the exception of the client feature, we have not made any changes that we believe will be
breaking unless you are overriding the internals of the package. The majority of applications
should therefore be able to upgrade without any changes.

### Clients

We have updated the client implementation to add the following:

- Support for relationship endpoints.
- Ability to pass raw payloads or records to serialize.
- Support include resources and sparse fieldsets when serializing records.

This is now feature complete for `1.0`. If you were already using clients, you will need to check
the updated documentation in the guides. In addition, please note that the namespace within the
package has changed to remove the nesting within the `Http` namespace. For example:

- `Contracts\Http\Client\ClientInterface` is now `Contracts\Client\ClientInterface`
- `Http\Client\GuzzleClient` is now `Client\GuzzleClient`.

The other major changes are:

- The `ClientInterface` has new methods and changes to existing method signatures. If you have
implemented the interface you will need to update your implementation.
- The client now always returns a PSR response, i.e. an instance of
`Psr\Http\Message\ResponseInterface`.
- The client now throws this exception: `\CloudCreaitivity\LaravelJsonApi\Exception\ClientException`.
- We have changed how records are serialized when sending create and update requests: refer to
the documentation for details.

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
