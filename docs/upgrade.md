# Upgrade Guide 

As we are currently on pre-1.0 releases, when you upgrade you will also need to specify that package
dependencies need to be upgraded. Use the following command:

```bash
$ composer require cloudcreativity/laravel-json-api:^0.10 --upgrade-with-dependencies
```

## Upgrading to 0.10 from 0.9

### Facade

If you are using the facade, you will need to change you `app.aliases` config to this:

```php
'aliases' => [
    // ...
    'JsonApi' => CloudCreativity\LaravelJsonApi\Facades\JsonApi::class,
],
```

We made this change so that you can import the class name as `JsonApi` via a `use` statement.

### Config

In each JSON API config file you will need to remove the `url-prefix` option and replace with this:

```php
/*
|--------------------------------------------------------------------------
| URL
|--------------------------------------------------------------------------
|
| The API's url, made up of a host, URL namespace and route name prefix.
|
| If a JSON API is handling an inbound request, the host will always be
| detected from the inbound HTTP request. In other circumstances
| (e.g. broadcasting), the host will be taken from the setting here.
| If it is `null`, the `app.url` config setting is used as the default.
|
| The name setting is the prefix for route names within this API.
|
*/
'url' => [
    'host' => null,
    'namespace' => '/api/v1',
    'name' => 'api:v1:',
],
```

### Routing

You now need to use `JsonApi::register()` to register routes for an API. This is because the `api()` method
is now used to get an API instance.

The `register()` method will also now automatically apply the URL prefix and route name prefix from your config 
file when registering routes.

For example, this:

```php
JsonApi::api('v1', ['prefix' => '/api/v1', 'as' => 'api-v1::', 'namespace' => 'ApiV1'], function ($api) {
    $api->resource('posts');
    $api->resource('comments');
});
```

Is now:

```php
JsonApi::register('v1', ['namespace' => 'ApiV1'], function ($api) {
    $api->resource('posts');
    $api->resource('comments');
});
```

> The URL prefix in your JSON API config is **always** relative to the root URL on a host, i.e. from `/`. 
This means when registering your routes, you need to ensure that no prefix has already been applied. The default
Laravel installation has an `api` prefix for API routes and you will need to remove this from your `mapApiRoutes()`
method in your `RouteServiceProvider` if your JSON API routes are being registered in your `routes/api.php` file.

### Non-Eloquent Controllers

The `ReplyTrait` has been renamed to:
`CloudCreativity\LaravelJsonApi\Http\Controllers\CreatesResponses`

This new trait has the same method `reply()` so you will not need to make any changes to your controllers.

The new trait also adds a handy `api()` method. This will return the API instance that is handling the inbound request.
This means if you were accessing the API's store in your controller, you can now access the store via this method. 
For example, in your `index()` action:

```php
public function index(RequestInterface $request)
{
    $records = $this->api()->getStore()->query(
        $request->getResourceType(),
        $request->getParameters()
    );

    return $this->reply()->content($records);
}
```

### Testing

We have upgraded the testing helpers to remove the dependency with `laravel/browser-kit-testing`. All the test
assertions have been moved to `CloudCreativity\LaravelJsonApi\TestResponse` (that extends the Illuminate class).
We have merged our `InteractsWithResources` trait into `MakesJsonApiRequests` because there were so few methods 
remaining. We have also removed the abstract methods and replaced with properties.

For example, this:

```php
use CloudCreativity\LaravelJsonApi\Testing\InteractsWithResources;

class PostsTest extends TestCase
{

  use InteractsWithResources;
  
  protected function getResourceType()
  {
      return 'posts';
  }
  
  protected function getRoutePrefix()
  {
      return 'api-v1::';
  }
}
```

Must be changed to this:

```php
use CloudCreativity\LaravelJsonApi\Testing\MakesJsonApiRequests;

class PostsTest extends TestCase
{

  use MakesJsonApiRequests;
  
  protected $resourceType = 'posts';
  
  protected $routePrefix = 'api-v1::';
}
```

When you call the following methods, they will return an instance of our `TestResponse`, rather than `$this`. The
test response is no longer assigned to a `$response` property on the test case (i.e. we made the same change as
Laravel):

- `jsonApi()`
- `doSearch()`
- `doCreate()`
- `doRead()`
- `doUpdate()`
- `doDelete()`

The same assertion methods have been moved to the `TestResponse`, with the following modifications:

- `assertIndexResponse`: use either `assertResourceResponse` or `assertResourcesResponse` to check whether the
`data` member has either a singular resource of the expected type, or a collection of resources.
- All methods starting `see...` have been deprecated and replaced with `assert...`. 
