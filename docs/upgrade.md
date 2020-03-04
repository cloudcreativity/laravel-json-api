# Upgrade Guide

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
