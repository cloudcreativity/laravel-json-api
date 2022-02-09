# Upgrade Guide

## 3.x to 4.0

To upgrade, run the following Composer commands:

```bash
composer remove cloudcreativity/json-api-testing --dev
composer require cloudcreativity/laravel-json-api:^4.0 --no-update
composer require laravel-json-api/testing:^1.1 --dev --no-update
composer up cloudcreativity/* laravel-json-api/*
```

### PHP 8.1

To remove deprecation messages from PHP 8.1, we've added return types or `#[\ReturnTypeWillChange]` annotations to
methods for internal interfaces. This is unlikely to break your application, unless you have extended one of our classes
and overridden an internal method.

### Testing

We are switching to using the `laravel-json-api/testing` package. This will help in the future with upgrading to the
new `laravel-json-api/laravel` package, because it will mean when you upgrade your tests should continue to work
without modifications. I.e. having a great test suite for your JSON:API functionality will help you safely upgrade in
the future.

The `laravel-json-api/testing` dependency uses the `cloudcreativity/json-api-testing` package, which is what you have 
been using up until now. It's just a more recent version, so there are a few changes to implement when upgrading.

The new testing package is fully 
[documented on the Laravel JSON:API site](https://laraveljsonapi.io/docs/1.0/testing/) so we recommend you take a look
at that if you get stuck when upgrading.

#### Test Case

In the test case where you're importing `MakesJsonApiRequests`, change the `use` statement from this:

```php
use CloudCreativity\LaravelJsonApi\Testing\MakesJsonApiRequests;
```

to this:

```php
use LaravelJsonApi\Testing\MakesJsonApiRequests;
```

#### Test Response

If anywhere in your application you have type-hinted our specific `TestResponse` you will need to change the `use`
statement. (Note that most applications won't have type-hinted the `TestResponse` anywhere.)

Previously:

```php
use CloudCreativity\LaravelJsonApi\Testing\TestResponse;
```

It now needs to be:

```php
use LaravelJsonApi\Testing\TestResponse;
```

#### Test Actions

The following test actions have been deprecated for some time and have now been removed. This is because these helper
methods used the JSON:API implementation internally, which was potentially risky as the JSON:API implementation itself
is the subject of the tests. These are the methods:

- `doSearch()`
- `doSearchById()`
- `doCreate()`
- `doRead()`
- `doUpdate()`
- `doDelete()`
- `doReadRelated()`
- `doReadRelationship()`
- `doReplaceRelationship()`
- `doAddToRelationship()`
- `doRemoveFromRelationship()`

Here are example replacements:

```php
// doSearch()
$response = $this->jsonApi('posts')->get('/api/v1/posts');

// doSearchById()
$response = $this
    ->jsonApi('posts')
    ->filter(['id' => $posts])
    ->get('/api/v1/posts');

// doCreate()
$response = $this
    ->jsonApi('posts')
    ->withData($data)
    ->post('/api/v1/posts');

// doRead()
$response = $this
    ->jsonApi('posts')
    ->get(url('/api/v1/posts', $post));

// doUpdate()
$response = $this
    ->jsonApi('posts')
    ->withData($data)
    ->patch(url('/api/v1/posts', $post));

// doDelete()
$response = $this
    ->jsonApi('posts')
    ->delete(url('/api/v1/posts', $post));

// doReadRelated()
$response = $this
    ->jsonApi('comments')
    ->get(url('/api/v1/posts', [$post, 'comments']));

// doReadRelationship()
$response = $this
    ->jsonApi('comments')
    ->get(url('/api/v1/posts', [$post, 'relationships', 'comments']));

// doReplaceRelationship()
$response = $this
    ->jsonApi('comments')
    ->withData($data)
    ->patch(url('/api/v1/posts', [$post, 'relationships', 'comments']));

// doAddToRelationship()
$response = $this
    ->jsonApi('comments')
    ->withData($data)
    ->post(url('/api/v1/posts', [$post, 'relationships', 'comments']));

// doRemoveFromRelationship()
$response = $this
    ->jsonApi('comments')
    ->withData($data)
    ->delete(url('/api/v1/posts', [$post, 'relationships', 'comments']));
```

> Note in all the above we use `withData()` to set the data for the request. Previously there was a `data` method, which
> has been removed.

Also, the following HTTP verb JSON:API methods have been removed:

- `getJsonApi` - use `$this->jsonApi('posts')->get($url)`
- `postJsonApi` - use `$this->jsonApi('posts')->withData($data)->post($url)`
- `patchJsonApi` - use `$this->jsonApi('posts')->withData($data)->patch($url)`
- `deleteJsonApi` - use `$this->jsonApi('posts')->withData($data)->delete($url)`

> For info, everything has been moved to the test builder that's returned by the `jsonApi()` method, so that we can 
> avoid collisions with any methods that Laravel might.

## 2.x to 3.0

### Validators

The method signature of the `rules()` method has changed so that the method has access to the data
that is going to be validated. You will need to amend the method signature on all of your validator
classes.

The method signature was previously:

```
protected function rules($record = null): array
{
    // ...
}
```

It is now:

```
protected function rules($record, array $data): array
{
    // ...
}
```

> Note that `$record` will still be `null` if the request will create a new resource.

### Soft Deletes

Previously if no soft deletes field was set on an adapter, the JSON API field would default to the dash-case
version of the soft deletes column on the model. For example, if the model used the column `deleted_at`,
the JSON API field would default to `deleted-at`.

In `v3`, the default is now the camel-case version of the column: i.e. `deleted_at` on the model would default
to `deletedAt` for the JSON API field. This change has been made because the JSON API spec has changed its
recommendation from using dash-case to camel-case.

If you have existing resources that use dash-case, simply set the `softDeleteField` property on your adapter,
for example:

```php
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Eloquent\Concerns\SoftDeletesModels;

class Adapter extends AbstractAdapter
{

    use SoftDeletesModels;

    protected $softDeleteField = 'deleted-at';

}
```

## 1.x to 2.0

Version 2 drops support for all 5.x and 6.x versions of Laravel, and sets the minimum PHP version to 7.2.
This is because Laravel 7 introduced a few changes (primarily to the exception handler and the namespace
of the test response class) that meant it was not possible to support Laravel 6 and 7.

This release is primarily a tidy-up release: we have removed all functionality that has been marked
as deprecated since the 1.0 pre-releases. Upgrading should be simple if you are not using any of the
deprecated pre-release features.

The following are some notes on additional upgrade steps.

### Errors

If you were type-hinting our error class, it has been moved from `Document\Error` to `Document\Error\Error`.
In addition, the `Validation\ErrorTranslator` class has been moved to `Document\Error\Translator`.

This will only affect applications that have customised error responses.

### Testing

The method signature of the test `jsonApi()` helper method on the `MakesJsonApiRequests` trait has been changed.
This now accepts no function arguments and returns a test builder instance that allows you to fluidly construct test
requests.

For example this on your test case:

```php
$response = $this->jsonApi('GET', '/api/v1/posts', ['include' => 'author']);
```

Is now:

```php
$response = $this
    ->jsonApi()
    ->includePaths('author')
    ->get('/api/v1/posts');
```

> Have a look at the `Testing/TestBuilder` class for the full list of methods you can use when building
> a test request.

All other test methods have been left on the `MakesJsonApiRequests` have been left, but we have marked a number
as deprecated. These deprecated methods will be removed in 3.0 in preference of using method chaining from the
`jsonApi()` method.

#### Test Query Parameters

As per [this issue](https://github.com/cloudcreativity/laravel-json-api/issues/427), we now fail a test if
any query parameters values are not strings, integers or floats. This is because query parameters are received
over HTTP as strings, so for example testing a `true` boolean is invalid and can lead to tests incorrectly
passing.
