# Custom Errors

## Introduction

This package allows you to return [JSON API error objects](http://jsonapi.org/format/1.0/#error-objects) which can include various information about what went wrong.

## Custom Errors In Controllers

This is particularly useful for errors that you have to manually present to the user through the controller. In order to magically return errors, you would need to use the `ErrorsAwareTrait`.


Below is an example of a scenario which a user fails to login due to incorrect credentials.

```php
namespace App\Http\Controllers\Api\V1;

use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface as JsonApiRequest;
use CloudCreativity\JsonApi\Document\Error;

class JsonWebTokensController extends Controller
{
    protected function guard()
    {
        return Auth::guard('jwt');
    }

    public function create(JsonApiRequest $request)
    {
        $resource_attributes = $request->getDocument()->getResource()->getAttributes();
        $credentials = [
            'email' => $resource_attributes->email,
            'password' => $resource_attributes->password,
        ];

        if ($this->guard()->attempt($credentials)) {
            // Success!
        } else {
            // Incorrect login details
            return $this->reply()->errors(Error::create([
            	'status' => 422,
            	'title' => 'Login failed.',
            	'detail' => 'These credentials do not match our records.'
            ]));

        }
    }
}
```

And the response given would be the following:

```json
{
    "errors": [
        {
            "status": "422",
            "title": "Login failed.",
            "detail": "These credentials do not match our records."
        }
    ]
}
```