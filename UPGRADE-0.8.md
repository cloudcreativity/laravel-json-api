# Upgrading from v0.7 to v0.8

## Key Changes

- Config is now defined on a per-API basis, with a separate config file for each API.
- The `Request` class has been removed to reduce the number of units per resource.
- The `Search` class has been merged into the `Adapter` unit to reduce the number of units per resource.

We have removed support for Laravel 5.1 and 5.2. The library no longer works with 5.1 because of a bug in Laravel
for which our PR to solve the problem was closed without being merged. Support for 5.2 has been dropped as 5.3 and 5.4
have now both been out for some time.

These are the steps to upgrade

## 1) Setup

To prevent errors when installing the new packages, do the following first:

1. Move the `config/json-api-errors.php` file out of the config directory to a safe place.
2. Move the `config/json-api.php` file out of the config directory to a safe place.
3. Comment out all route definitions for your JSON API routes.

Then:

```bash
$ composer require cloudcreativity/laravel-json-api:^0.8 --update-with-dependencies
```

## 2) Config

Create an API configuration file:

```bash
$ php artisan make:json-api
```

> If you have multiple APIs, you can create config for each API using `php artisan make:json-api <name>`

Transfer the config from your old `json-api.php` config file into the new file, noting the following:

1. There are no paging settings in the new config file (that's now in the `Adapter` class).
2. Rather than defining a schema map, you now define a map of JSON API resource types to the model/entity
class to which they relate. E.g. `'posts' => App\JsonApi\Posts\Schema::class` in your old schemas config
would now be added to the resources list in the new config as `'posts' => App\Post::class`.
3. None of the adapter config needs to be transferred.

Any custom errors in the `json-api-errors.php` file needs to be transferred to the `errors` key in the
new API config file. You ONLY need to transfer custom errors - default errors provided by the package are
now automatically added.

Once you're finished you can delete your old `json-api.php` and `json-api-errors.php` files.

## 3) Routing

Routing has been improved. If you had this:

```php
Route::group([
  'prefix' => 'api',
  'as' => 'api::',
  'namespace' => 'Api',
  'middleware' => ['json-api:default', 'my-middleware'],
], function () {
  JsonApi::resource('posts');
  JsonApi::resource('comments');
});
```

It would now become this:

```php
JsonApi::api('default', [
  'prefix' => 'api',
  'as' => 'api::',
  'namespace' => 'Api',
  'middleware' => ['my-middleware'],
], function ($api, $router) {
  $api->resource('posts', ['has-many' => ['comments', 'tags']);
  $api->resource('comments', ['has-one' => 'post']);
});
```

> The routing functionality now includes a lot more options to allow you to only register the exact JSON API routes
that you want per-resource and per-resource-relationship. See the Wiki for full details.

## 4) Authorizers

Any authorizer classes that you have written need `$resourceType` added as the first parameter for the `canCreate`
and `canReadMany` methods. See the `AuthorizerInterface` for the updated signature.

> We've added this parameter because it is common to have authorizers that handle multiple resource types. 
For instance we are planning to add a generic Laravel authorizer to this library in the near future.

## 5) Validators

Validator classes are so specific to a resource type that we have decided to remove the `$resourceType` parameter
from all methods on the class. You will need to update you validator methods accordingly, and add the `$resourceType`
property to the class.

We have also renamed the following attributes and methods:

- `filterRules` becomes `queryRules`
- `filterMessages` becomes `queryMessages`
- `filterCustomAttributes` becomes `queryCustomAttributes`

These now validate filter, paging and unrecognized parameters, which means you need to add `filter.` to the
front of any rules that previously validated filters.

For example, this:

```php
class Validators extends AbstractValidatorProvider
{
  // ...
  
  protected $filterRules = ['title' => 'string|min:1'];
  
  protected function attributeRules($resourceType, $record = null)
  {
    // ...
  }
  
  protected function relationshipRules(RelationshipsValidatorInterface $relationships, $resourceType, $record = null)
  {
    // ...
  }
}
```

Becomes this:

```php
class Validators extends AbstractValidatorProvider
{
  protected $resourceType = 'posts';
  
  protected $queryRules = [
    'filter.title' => 'string|min:1',
    // example page parameter validation...
    'page.number' => 'integer|min:1',
    'page.size' => 'integer|between:1,50',
  ];

  // ...

  protected function attributeRules($record = null)
  {
    // ...
  }
  
  protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
  {
    // ...
  }
}
```

If you are using a single validator class for multiple resource types, you can refactor to use extension or traits
to provide the abstraction required.

## 6) Requests

Your `Request` classes for each resource can be removed once you have transferred their settings to new locations.

### Relationships

The following properties should be defined in your route configuration:

- `hasMany`: use the `has-many` option as per above example.
- `hasOne`: use the `has-one` option as per above example.

### Allowed Query Parameters

The following properties should be moved to the `Validators` class for the resource:

- `allowedSortParameters`
- `allowedFilteringParameters`
- `allowUnrecognizedParameters`
- `allowedIncludePaths`
- `allowedFieldSetTypes`

Allowed paging parameters can be set using the `allowedPagingParameters` parameter on the `Validators` class.

### Authorizers

If you were injecting an `Authorizer` instance into the `Request` constructor, authorizers are now automatically
detected. E.g. for the `posts` resource it will look for `Posts\Authorizer` if you are storing classes by resource,
or `Authorizers\Post` if not storing by resource.

If you need to use a different `Authorizer` class, this is defined in route configuration, for example:

```php
JsonApi::api('default', [], function ($api, $router) {
  $api->resource('posts', ['authorizer' => MyAuthorizer::class]);
});
```

> Route configuration allows for a default authorizers to be set. See the authorizer page in the Wiki for 
more information.

### Validators

If you were injecting a `Validators` class into the `Request` constructor, these are now automatically detected
either as `Posts\Validators` or `Validators\Post` depending on your API's `by-resource` config setting.

If you need to use a non-standard validator class, you can define this in your route configuration, for example:

```php
JsonApi::api('default', [], function ($api, $router) {
  $api->resource('posts', ['validators' => MyValidators::class]);
});
```

## 7) Adapters / Search

### Eloquent

If your resource type does not have a `Search` class, generate a new adapter:

```bash
$ php artisan make:json-api:adapter <resource> [<api>] -e
```

If it does have a `Search` class, rename it to `Adapter` and extend `EloquentAdapter`. 
You will then need to add a constructor that injects a model instance and a paging strategy (if needed).
 
For example, this:

```php
use CloudCreativity\LaravelJsonApi\Search\AbstractSearch;

class Search extends AbstractSearch
{
  protected function filter(Builder $builder, Collection $filters)
  {
    // ...
  }

  protected function isSearchOne(Collection $filters)
  {
    return false;
  }
```

Is changed to this:

```php
use App\Post;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use CloudCreativity\LaravelJsonApi\Store\EloquentAdapter;

class Adapter extends EloquentAdapter
{

  public function __construct(StandardStrategy $paging)
  {
    // $paging does not need to be provided if the resource cannot be paged.
    parent::__construct(new Post(), $paging);
  }

  protected function filter(Builder $builder, Collection $filters)
  {
    // ...
  }

  protected function isSearchOne(Collection $filters)
  {
    return false;
  }
```

If you were previously using the `$perPage` or `$maxPerPage` attributes on the `Search` instance, you must
now set page validation rules in the `Validators` class:

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

If you want to force your resource to always be paginated even if the client does not provide page parameters,
then on your `Adapter` set the `$defaultPagination` attribute to an array of the paging parameters to use
if the client has not provided any. E.g.:

```php
class Adapter extends EloquentAdapter
{
  protected $defaultPagination = [
    'number' => 1,
    'size' => 50,
  ];
}
```

### Paging Strategies

Paging can now be customised on a per-resource basis by injecting a paging strategy into the adapter. The one
provided by the package - `StandardStrategy` - is standard page number/size pagination.

The `StandardStrategy` instance has a number of methods (starting with `with`) that allow you to customise
its output. E.g.:

```php
class Adapter extends EloquentAdapter
{
  public function __construct(StandardStrategy $paging)
  {
    $paging->withMetaKey('page')->withUnderscoredMetaKeys();
    parent::__construct(new Post(), $paging);
  }
}
```

If you need to customise the pagination for multiple resources, create a new class that extends `StandardStrategy`
and inject that into your `Adapter` instead.

### Non-Eloquent

The `AdapterInterface` has changed: in summary, there is now a single adapter per resource type, and it also now
supports the querying of resources. Generally you will be able to write an abstract class (such as our
`EloquentAdapter`) and extend from that if your entities share common adapter functionality.

To generate an adapter for a resource that is not Eloquent:

```bash
$ php artisan make:json-api:adapter <resource> [<api>] -N
```

## 8) Controllers

### Eloquent Controllers

Make the following changes:

- Remove all references to the old search classes (you do NOT need to change 
them to the new `Adapter` class).
- Delete the `getRequestHandler` method.
- If you have extended the `index` method, you will need to update the method signature by adding `ApiInterface $api`
as the first parameter (refer to the parent controller).

For example, this:

```php
use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;
use App\Post;
use App\JsonApi\Posts;

class PostsController extends EloquentController
{
  public function __construct(Posts\Hydrator $hydrator, Posts\Search $search)
  {
    parent::__construct(new Post(), $hydrator, $search);
  }

  protected function getRequestHandler()
  {
    return Posts\Request::class;
  }
}
```

Would change to this:

```php
use CloudCreativity\LaravelJsonApi\Http\Controllers\EloquentController;
use App\Post;
use App\JsonApi\Posts;

class PostsController extends EloquentController
{
  public function __construct(Posts\Hydrator $hydrator)
  {
    parent::__construct(new Post(), $hydrator);
  }
}
```

### Non-Eloquent Controllers

You can remove the `getRequestHandler()` method.

Please note that the `JsonApiController` now contains little of value and it is our intention to remove it in
a future version. For example, our `EloquentController` no longer extends from it.

If you want to stop extending it, then remember to add the traits to your controller that it uses.

## 9) Links

All routes registered for a resource now have a route name - check the routing section in the Wiki for details.
As such, we've updated the link factory, as follows:

- removed `resource()`: use `read()`, `update()` or `delete()`
- removed `relationship()`: use `readRelationship()`, `replaceRelationship()`, `addRelationship()`, `removeRelationship()`
instead.
