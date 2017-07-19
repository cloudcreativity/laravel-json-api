# Pagination

## Introduction

This package integrates with Laravel's query builder pagination to provide support for the 
[JSON API paging feature](http://jsonapi.org/format/#fetching-pagination).

JSON API designates that the `page` query parameter is reserved for paging parameters. However, the spec is agnostic
as to how the server will implement paging. In this package, the server implementation for paging is known as a 
*paging strategy*.

This package provides a standard strategy that provides paging using the `page[number]` and `page[size]` query 
parameters, which neatly maps to Laravel's paging implementation. You can write your own paging strategies and
you can choose which strategy to use for each resource type.

## Page Pagination

The page-based strategy provided by this package is called the *standard strategy*. To use this for Eloquent models,
you use constructor injection on your `Adapter` class. For example:

```php
namespace App\JsonApi\Posts;

use App\Post;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use CloudCreativity\LaravelJsonApi\Store\EloquentAdapter;

class Adapter extends EloquentAdapter
{

    /**
     * @param StandardStrategy $paging
     */
    public function __construct(StandardStrategy $paging)
    {
        parent::__construct(new Post(), $paging);
    }

    // ...

}
```

> If you use Artisan generators - e.g. `make:json-api:resource` - then your Eloquent adapter will already have the
standard strategy injected via its constructor.

This means the following request:

```http
GET /api/posts?page[number]=2&page[size]=15 HTTP/1.1
Accept: application/vnd.api+json
```

Will receive a paged response:

```http
HTTP/1.1 200 OK
Content-Type: application/vnd.api+json

{
  "meta": {
    "page": {
      "current-page": 2,
      "per-page": 15,
      "from": 16,
      "to": 30,
      "total": 50,
      "last-page": 4
    }
  },
  "links": {
    "first": "http://localhost/api/v1/posts?page%5Bnumber%5D=1&page%5Bsize%5D=15",
    "prev": "http://localhost/api/v1/posts?page%5Bnumber%5D=1&page%5Bsize%5D=15",
    "next": "http://localhost/api/v1/posts?page%5Bnumber%5D=3&page%5Bsize%5D=15",
    "last": "http://localhost/api/v1/posts?page%5Bnumber%5D=4&page%5Bsize%5D=15"
  },
  "data": [...]
}
```

### Per-Resource Customisation

The standard strategy provides a number of methods (described below) to customise the pagination. To customise the
strategy for a specific resource, call the methods in your constructor. For example:

```php
class Adapter extends EloquentAdapter
{

    /**
     * @param StandardStrategy $paging
     */
    public function __construct(StandardStrategy $paging)
    {
        $paging->withPageKey('page')->withPerPageKey('limit');
        parent::__construct(new Post(), $paging);
    }

    // ...
}
```

### Default Customisation

If you want to override the defaults for many resource types, bind a strategy to your service container in
your `AppServiceProvider`:

```php
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;

class AppServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->app->bind('my-paging-strategy', function ($app) {
            $strategy = $app->make(StandardStrategy::class);
            $strategy->withPageKey('page')->withPerPageKey('limit');
            return $strategy;
        });
    }
}
```

Then in your adapter:

```php
class Adapter extends EloquentAdapter
{

    public function __construct()
    {
        parent::__construct(new Post(), app('my-paging-strategy'));
    }

    // ...
}
```

### Customising the Query Parameters

By default, the page parameters used a `number` and `size`. To customise either, use the `withPageKey` and
`withPerPageKey` methods. E.g. if this was used:

```php
$strategy->withPageKey('page')->withPerPageKey('limit');
```

The client would need to send the following request:

```http
GET /api/posts?page[page]=2&page[limit]=15 HTTP/1.1
Accept: application/vnd.api+json
```

### Using Simple Pagination

The standard strategy uses Laravel's length aware pagination by default. To use simple pagination instead:

```php
$strategy->withSimplePagination();
```

> Using simple pagination means the HTTP response content will not contain details of the last page and total records
available.

### Customising Page Meta

As shown in the example HTTP request/response above, the default strategy adds paging information to the `page` key
of your response's `meta`. By default the meta keys are dasherized - e.g. `per-page`.

You can change the key that the meta is added to and underscore the page meta keys using the following methods:

```php
$strategy->withUnderscoredMetaKeys()->withMetaKey('current_page');
```

This would result in the following meta in your HTTP response:

```json
{
    "meta": {
        "current_page": {
              "current_page": 2,
              "per_page": 15,
              "from": 16,
              "to": 30,
              "total": 50,
              "last_page": 4
        }
    }
}
```

You can disable nesting of the page details in the top-level `meta` using the following:

```php
$strategy->withMetaKey(null);
```

Will result in the following:

```json
{
    "meta": {
        "current-page": 2,
        "per-page": 15,
        "from": 16,
        "to": 30,
        "total": 50,
        "last-page": 4
    }
}
```

## Validating Paging Parameters

You should validate page parameters that are sent from a client, and this is supported on your resource's 
`Validators` class. For example, you could ensure that the client never requests more than 50 resources per-page
using the following validation rules:

```php
class Validators extends AbstractValidatorProvider
{

    protected $queryRules = [
        'page.number' => 'integer|min:1',
        'page.size' => 'integer|between:1,50',
    ];
    
    // ...
}
```

## Forcing Pagination

There are some resources that you will always want to be paginated - because without pagination, your API would
return too many records in one request.

To force a resource to be paginated even if the client does not send pagination paremeters, use the 
`$defaultPagination` option on your Eloquent adapter. The parameters you set in this property are used by the
adapter if the client does not provide any page parameters. For example, the following will force the first page
to be used for the standard paging strategy:

```php
class Adapter extends EloquentAdapter
{

    protected $defaultPagination = ['number' => 1];

    // ...

}
```

> For the standard strategy, there is no need to provide a default page `size`. If none is provided, 
Eloquent will use the default as set on your model's class.

If you need to programmatically work out the default paging parameters, overload the `defaultPagination` method. 
For example, if you had a date based pagination strategy:

```php
class Adapter extends EloquentAdapter
{

    // ...
    
    protected function defaultPagination()
    {
        return [
            'from' => Carbon::now()->subMonth()->toW3cString(),
            'to' => Carbon::now()->toW3cString()
        ];
    }

}
```

The default pagination property is an array so that you can use it with any paging strategy.

## Custom Paging Strategies

If you need to write your own strategies, create a class that implements the `PagingStrategyInterface`. 
For example:

```php
use CloudCreativity\LaravelJsonApi\Contracts\Pagination\PagingStrategyInterface;

class DateRangeStrategy implements PagingStrategyInterface
{
    public function paginate($query, EncodingParametersInterface $pagingParameters)
    {
        // ...paging logic here, that returns a JSON API page object.
    }
}
```

We recommend you look through the code for our `StandardStrategy` to see how to implement a strategy.

> We plan to extract as much as the functionality of the `StandardStrategy` as possible into traits, but have not 
got to this yet. If you are writing your own strategy and feel like submitting a PR, that would be great!

You can then inject your new strategy into your adapters as follows:

```php
class Adapter extends EloquentAdapter
{

    /**
     * @param DateRangeStrategy $paging
     */
    public function __construct(DateRangeStrategy $paging)
    {
        parent::__construct(new Post(), $paging);
    }

    // ...

}
```

> If you write a strategy that you think is generic to Laravel, then we will happily consider including it in this
package if you submit a PR.
