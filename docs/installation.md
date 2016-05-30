# Installation

Install using [Composer](http://getcomposer.org):

``` bash
$ composer require cloudcreativity/laravel-json-api
```

Add the package service provider to your `config/app.php` providers array.

``` php
'providers' => [
  // ...existing providers
  'CloudCreativity\LaravelJsonApi\ServiceProvider'
]
```

If you would like to use the `JsonApi` facade, add the following to the list of aliases in the same file
(`config/app.php`):

``` php
'aliases' => [
  // ... existing aliases
  'JsonApi' => 'CloudCreativity\LaravelJsonApi\Facade'
]
```

The `JsonApi` facade maps to the `CloudCreativity\LaravelJsonApi\Services\JsonApiService` class.

Then publish the package config file:

``` bash
$ php artisan vendor:publish --provider="CloudCreativity\JsonApi\ServiceProvider"
```

## Configuration

Two configuration files are published:

1. `json-api.php` which contains your application's configuration for the JSON API integration.
2. `json-api-errors.php` which contains array representations of JSON API errors that are returned by this
package's validators if there are errors in the HTTP request content received from a client.

> Configuration settings are described in each chapter of this documentation.

## Exception Handling

Parts of the package throw exceptions to abort execution and render JSON API errors. Your will therefore need to
add support for JSON API error rendering to your application's exception handler.

To do this, simply add the `CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors` trait to your handler and
modify your `render()` method as follows:

``` php
namespace App\Exceptions;

use CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{

	use HandlesErrors;

	// ...

    public function render($request, \Exception $e)
    {
    	if ($this->isJsonApi()) {
        return $this->renderJsonApi(\Exception $e);
      }

      // do standard exception rendering here...
    }
}
```
