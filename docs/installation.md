# Installation

## Laravel 5.5

Install using [Composer](http://getcomposer.org):

``` bash
$ composer require cloudcreativity/laravel-json-api:1.0.0-alpha.2
$ composer require --dev cloudcreativity/json-api-testing
```

This package's service provider and facade will be automatically added using package discovery. You will
need to follow the instructions below for updating your `ExceptionHandler`.

## Laravel 5.4

Install using [Composer](http://getcomposer.org):

``` bash
$ composer require cloudcreativity/laravel-json-api:1.0.0-alpha.2
$ composer require --dev cloudcreativity/json-api-testing
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
  'JsonApi' => CloudCreativity\LaravelJsonApi\Facades\JsonApi::class,
]
```

## Exception Handling

Parts of the package throw exceptions to abort execution and render JSON API errors. Your will therefore need to
add support for JSON API error rendering to your application's exception handler.

To do this, simply add the `CloudCreativity\LaravelJsonApi\Exceptions\HandlesErrors` trait to your handler and
modify your `render()` method as follows:

``` php
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
      if ($this->isJsonApi()) {
        return $this->renderJsonApi($request, $e);
      }

      // do standard exception rendering here...
    }
}
```
