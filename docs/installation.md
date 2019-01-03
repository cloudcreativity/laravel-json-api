# Installation

Install using [Composer](http://getcomposer.org):

``` bash
$ composer require cloudcreativity/laravel-json-api:1.0.0-beta.6
$ composer require --dev cloudcreativity/json-api-testing:1.0.0-rc.1
```

This package's service provider and facade will be automatically added using package discovery. You will
then need to update your Exception handler as follows...

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
      if ($this->isJsonApi($request, $e)) {
        return $this->renderJsonApi($request, $e);
      }

      // do standard exception rendering here...
    }
}
```
