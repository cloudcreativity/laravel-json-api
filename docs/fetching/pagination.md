# Pagination

## Introduction

This package provides comprehensive support for the 
[JSON API paging feature](http://jsonapi.org/format/#fetching-pagination).

JSON API designates that the `page` query parameter is reserved for paging parameters. However, the spec is agnostic
as to how the server will implement paging. In this package, the server implementation for paging is known as a 
*paging strategy*.

This package provides two paging strategies:

- **Page-based**: default Laravel pagination using a page number and size query parameters.
- **Cursor-based**: cursor pagination inspired by Stripe's implementation.

You can choose a paging strategy for each resource type, so your API can use different strategies for
different resource types if needed. If neither the page-based or cursor-based pagination provided  meet your needs,
you can write your own paging strategies.

> The Eloquent adapter will automatically use any pagination strategy that is injected via the constructor, as
shown in the examples below. If you have a custom adapter, you can still use the strategies provided by
this package but you will need to invoke them yourself.

## Disallowing Pagination

If your resource does not support pagination, you should reject any request that contains the `page`
parameter. You can do this by disallowing page parameters on your [Validators](../basics/validators.md)
class as follows:

```php
class Validators extends AbstractValidators
{
    // ...
    
    protected $allowedPagingParameters = [];

}
```

## Page-Based Pagination

The page-based strategy provided by this package is implemented as the `StandardStrategy` class, because
it matches Laravel's standard paging implementation.

Our implementation uses the `number` and `size` page parameters:

| Parameter | Description |
| :--- | :--- |
| `number` | The page number that the client is requesting. |
| `size` | The number of resources to return per-page. |

> You change the name of these parameters if desired: see the customisation section below.

To use page-based pagination for Eloquent models, inject the strategy in the constructor of your `Adapter` class.
For example:

```php
namespace App\JsonApi\Posts;

use App\Post;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;

class Adapter extends AbstractAdapter
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
    "first": "http://localhost/api/v1/posts?page[number]=1&page[size]=15",
    "prev": "http://localhost/api/v1/posts?page[number]=1&page[size]=15",
    "next": "http://localhost/api/v1/posts?page[number]=3&page[size]=15",
    "last": "http://localhost/api/v1/posts?page[number]=4&page[size]=15"
  },
  "data": [...]
}
```

> The query parameters in the above examples would be URL encoded, but are shown without encoding for
readability.

### Customisation

The page-based strategy provides a number of methods (described below) to customise the pagination.
To customise the strategy for a specific resource, call the methods in your constructor. For example:

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

### Customising the Query Parameters

To change the default parameters of `number` and `size` use the `withPageKey` and `withPerPageKey` methods.
E.g. if this was used:

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

> Using simple pagination means the HTTP response content will not contain details of the last page and
total resources available.

### Validation

You should always validate page parameters that are sent from a client, and this is supported on your resource's
[Validators](../basics/validators.md) class. It is highly recommended that you validate the page-based
parameters to ensure the values are numbers and the page size is within an acceptable range.

For example on your validators class:

```php
class Validators extends AbstractValidators
{

    protected $allowedPagingParameters = ['number', 'size'];

    protected $queryRules = [
        'page.number' => 'filled|numeric|min:1',
        'page.size' => 'filled|numeric|between:1,100',
    ];

    // ...
}
```

## Cursor-Based Pagination

The cursor-based strategy provided by this package is inspired by
[Stripe's pagination implementation](https://stripe.com/docs/api#pagination).

Cursor-based pagination is based on the paginator being given a context as to what results to return
next. So rather than an API client saying it wants page number 2, it instead says it wants the items
in the list after the last item it received. This is ideal for infinite scroll implementations, or
for resources where rows are regularly inserted (which would affect page numbers if you used paged-based
pagination).

This pagination is based on the list being in a fixed order. This means that if you use cursor-based
pagination for a resource type, you should not support sort parameters as this can have adverse effects
on the cursor pagination.

Our implementation utilizes cursor-based pagination via the `after` and `before` page parameters.
Both parameters take an existing resource ID value (see below) and return resources in a fixed order.
By default this fixed order is reverse chronological order (i.e. most recent first, oldest last).
The `before` parameter returns resources listed before the named resource. The `after` parameter
returns resources listed after the named resource. If both parameters are provided, only `before`
is used. If neither parameter is provided, the first page of results will be returned.

| Parameter | Description |
| :--- | :--- |
| `limit` | A limit on the number of resources to be returned. |
| `after` | A cursor for use in pagination. `after` is a resource ID that defines your place in the list. For instance, if you make a paged request and receive 100 resources, ending with resource with id `foo`, your subsequent call can include `page[after]=foo` in order to fetch the next page of the list. |
| `before` | A cursor for use in pagination. `before` is a resource ID that defines your place in the list. For instance, if you make a paged request and receive 100 resources, starting with resource with id `bar` your subsequent call can include `page[before]=bar` in order to fetch the previous page of the list. |

> You change the name of these parameters if desired: see the customisation section below.

To use cursor-based pagination for Eloquent models, inject the cursor strategy via the constructor on
your `Adapter` class. For example:

```php
namespace App\JsonApi\Comments;

use App\Comment;
use CloudCreativity\LaravelJsonApi\Pagination\CursorStrategy;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;

class Adapter extends AbstractAdapter
{

    /**
     * @param CursorStrategy $paging
     */
    public function __construct(CursorStrategy $paging)
    {
        parent::__construct(new Post(), $paging);
    }

    // ...

}
```

This means the following request:

```http
GET /api/posts?page[limit]=5&page[after]=03ea3065-fe1f-476a-ade1-f16b40c19140 HTTP/1.1
Accept: application/vnd.api+json
```

Will receive a paged response:

```http
HTTP/1.1 200 OK
Content-Type: application/vnd.api+json

{
  "meta": {
    "page": {
      "per-page": 10,
      "from": "bfdaa836-68a3-4427-8ea3-2108dd48d4d3",
      "to": "df093f2d-f042-49b0-af77-195625119773",
      "has-more": true
    }
  },
  "links": {
    "first": "http://localhost/api/v1/posts?page[limit]=10",
    "prev": "http://localhost/api/v1/posts?page[limit]=10&page[before]=bfdaa836-68a3-4427-8ea3-2108dd48d4d3",
    "next": "http://localhost/api/v1/posts?page[limit]=10&page[after]=df093f2d-f042-49b0-af77-195625119773"
  },
  "data": [...]
}
```

> The query parameters in the above examples would be URL encoded, but are shown without encoding for
readability.

### Customisation

The cursor strategy provides a number of methods (described below) to customise the pagination. To customise the
strategy for a specific resource, call the methods in your constructor. For example:

```php
class Adapter extends EloquentAdapter
{

    /**
     * @param CursorStrategy $paging
     */
    public function __construct(CursorStrategy $paging)
    {
        $paging->withBeforeKey('ending-before')->withAfterKey('starting-after');
        parent::__construct(new Post(), $paging);
    }

    // ...
}
```

### Customising the Query Parameters

To change the default parameters of `limit`, `after` and `before`, use the `withLimitKey`, `withAfterKey`
and `withBeforeKey` methods as needed. For example:

```php
$strategy->withLimitKey('size')
    ->withAfterKey('starting-after')
    ->withBeforeKey('ending-before');
```

The client would need to send the following request:

```http
GET /api/posts?page[size]=25&page[starting-after]=df093f2d-f042-49b0-af77-195625119773 HTTP/1.1
Accept: application/vnd.api+json
```

### Customising the Pagination Column

By default the strategy uses a model's created at column in descending order for the list order. This
means the most recently created model is the first in the list, and the oldest is last. As the created at
column is not unique (there could be multiple rows created at the same time), it uses the resource id
column as a secondary sort order, as the resource id must always be unique.

To change the column that is used for the list order use the `withQualifiedColumn` method. If you prefer
your list to be in ascending order, use the `withAscending` method. For example:

```php
$strategy->withQualfiedColumn('posts.published_at')->withAscending();
```

> The Eloquent adapter will always set the secondary column for sort order to the same column that is
being used for the resource ID. If you are using the cursor strategy in a custom adapter, you will
need to set the unique column using the `withQualifiedKeyName` method. Note that whatever you set the
column to, this will mean the client needs to provide the value of that column for the `after` and
`before` page parameters - so really it should always match whatever you are using for the resource ID.

### Validation

You should always validate page parameters that are sent from a client, and this is supported on your resource's 
[Validators](../basics/validators.md) class. You **must** validate that the identifier provided by the client
for the `after` and `before` parameters are valid identifiers, because invalid identifiers cause an error
in the cursor. It is also recommended that you validate the `limit` so that it is within an acceptable range.

As the cursor relies on the list being in a fixed order (that it controls), you **must** also disable
sort parameters. This can also be done on your resource's `Validators` class. For example:

```php
class Validators extends AbstractValidators
{

    // disable all sort parameters.
    protected $allowedSortParameters = [];
    
    protected $allowedPagingParameters = ['limit', 'after', 'before'];

    protected $queryRules = [
        'page.limit' => 'filled|numeric|between:1,100',
        'page.after' => 'filled|string|exists:comments,id',
        'page.before' => 'filled|string|exists:comments,id'
    ];

    // ...
}
```

## Page Meta

As shown in the example HTTP responses in this chapter, the page and cursor strategies add paging
information to the `page` key of your response's top-level `meta` member. By default the
meta keys are dasherized - e.g. `per-page`.

You can change the key that the meta is added to and underscore the page meta keys using the following
methods on either the page-based or cursor-based strategies:

```php
$strategy->withUnderscoredMetaKeys()->withMetaKey('current_page');
```

This would result in the following meta in your HTTP response (using  a page-based strategy as an example):

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
    },
    "data": [...]
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
    },
    "data": [...]
}
```

## Default Customisation

If you want to override the defaults for many resource types, then you can extend either the page-based or
cursor-based strategy and inject your child class into your adapters. For example:

```php
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;

class CustomStrategy extends StandardStrategy
{

    public function __construct()
    {
        parent::__construct();
        $this->withPageKey('page')->withPerPageKey('limit');
    }
}
```

Then in your adapter:

```php
class Adapter extends AbstractAdapter
{

    public function __construct(CustomStrategy $paging)
    {
        parent::__construct(new Post(), $paging);
    }

    // ...
}
```

## Forcing Pagination

There are some resources that you will always want to be paginated - because without pagination, your API would
return too many records in one request.

To force a resource to be paginated even if the client does not send pagination parameters, use the
`$defaultPagination` option on your Eloquent adapter. The parameters you set in this property are used by the
adapter if the client does not provide any page parameters. For example, the following will force the first page
to be used for the page-based strategy:

```php
class Adapter extends EloquentAdapter
{

    protected $defaultPagination = ['number' => 1];

    // ...

}
```

Or for the cursor-based strategy:

```php
protected $defaultPagination = ['limit' => 10];
```

> For the page-based strategy, there is no need to provide a default page `size`. If none is provided,
Eloquent will use the default as set on your model's class.

If you need to programmatically work out the default paging parameters, overload the `defaultPagination` method. 
For example, if you had written a custom date-based pagination strategy:

```php
class Adapter extends EloquentAdapter
{

    // ...
    
    protected function defaultPagination()
    {
        return [
            'from' => Carbon::now()->subMonth()->toAtomString(),
            'to' => Carbon::now()->toAtomString()
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

> If you write a strategy that you think other Laravel users might use, please consider open-sourcing it.
Let us know and we will add a link to alternative strategies here.
