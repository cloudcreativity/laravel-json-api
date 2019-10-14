# Installation

Install using [Composer](http://getcomposer.org):

```bash
$ composer require cloudcreativity/laravel-json-api
$ composer require --dev "cloudcreativity/json-api-testing:^1.2|^2.0"
```

This package's service provider and facade will be automatically added using package discovery. You will
then need to check your API route prefix and update your Exception handler as follows...

## Route Prefixes

The default Laravel installation has an `api` prefix for API routes. If you want to register your JSON API
routes in your `routes/api.php` file, you will need to remove the prefix from the `mapApiRoutes()` method in your 
`RouteServiceProvider`.

For example, change this:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    // ...

    protected function mapApiRoutes()
    {
        Route::prefix('api')
             ->middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}
```

To this:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    // ...

    protected function mapApiRoutes()
    {
        Route::middleware('api')
             ->namespace($this->namespace)
             ->group(base_path('routes/api.php'));
    }
}
```

## Exception Handling

Parts of the package throw exceptions to abort execution and render JSON API errors. Your will therefore need to
add support for JSON API error rendering to your application's exception handler.

To do this, simply add the `CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors` trait to your handler and
modify your `render()` method as follows:

```php
namespace App\Exceptions;

use CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors;
use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Neomerx\JsonApi\Exceptions\JsonApiException;

class Handler extends ExceptionHandler
{

	use HandlesErrors;

	protected $dontReport = [
	  // ... other exception classes
	  JsonApiException::class,
	];

	// ...

  public function render($request, Exception $e)
  {
    if ($this->isJsonApi($request, $e)) {
      return $this->renderJsonApi($request, $e);
    }

    // do standard exception rendering here...
  }
  
  protected function prepareException(Exception $e)
  {
      if ($e instanceof JsonApiException) {
        return $this->prepareJsonApiException($e);
      }

      return parent::prepareException($e);
  }
}
```
