# Controllers

This chapter describes how to implement your own controller for processing JSON API requests and returning JSON API
responses.

## Setup

In order to send JSON API responses, you will need to apply the `ReplyTrait` to your controller. If your resource
has a hydrator, you can also inject it via the constructor.

Here is an example controller:

```php
namespace App\Http\Controllers\Api;
 
use App\JsonApi\Users;
use CloudCreativity\LaravelJsonApi\Http\Responses\ReplyTrait;
use Illuminate\Routing\Controller;
 
class UsersController extends Controller
{

    use ReplyTrait;

    private $hydrator;
    
    public function __construct(Users\Hydrator $hydrator)
    {
        $this->hydrator = $hydrator;
    }
    
    // Methods as per below...
}
```

## Resource Actions

As described in the [routing](../routing.md) chapter, there are five resource actions for which you need to implement
methods:

1. `index()`
2. `create()`
3. `read()` 
4. `update()`
5. `delete()`

They are able to accept a few parameters and must make use of the reply trait to return a response.

The following are examples covering these functions. Do take note that you will need to import the namespaces 
correctly.

### Index

```php
/**
 * @param \CloudCreativity\JsonApi\Contracts\Http\ApiInterface $api
 * @param \CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface $request
 * @return \Illuminate\Http\Response
 */
public function index(ApiInterface $api, RequestInterface $request)
{
    $result = $api->getStore()->query(
        $request->getResourceType(),
        $request->getParameters()
    );
    
    return $this->reply()->content($result);
}
```

### Create

```php
/**
 * @param \CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface $request
 * @return \Illuminate\Http\Response
 */
public function create(RequestInterface $request)
{
    $resource = $request->getDocument()->getResource();
    // As an example, if we wanted to wrap the change in a transaction...
    $record = \DB::transaction(function () use ($resource, $request) {
        $record = new User();
        $this->hydrator->hydrate($request->getDocument()->getResource(), $record);
        $record->save();
        
        return $record;
    });
    
    return $this->reply()->created($record);
}
```

### Read

```php
/**
 * @param \CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface $request
 * @return \Illuminate\Http\Response
 */
public function read(RequestInterface $request)
{
    return $this->reply()->content($request->getRecord());
}
```
### Update

```php
/**
 * @param \CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface $request
 * @return \Illuminate\Http\Response
 */
public function update(RequestInterface $request)
{
    /** @var User $record */
    $record = $request->getRecord();
    $resource = $request->getDocument()->getResource();
    $this->hydrator->hydrate($resource, $record);
    $passwordChanged = $record->hasPasswordChanged();
    $record->save();
    
    // for example, if we wanted to fire an event...
    if ($passwordChanged) {
      event(new UserChangedPassword($record));
    }
    
    return $this->reply()->content($record);
}
```

### Delete

```php
/**
 * @param \CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface $request
 * @return \Illuminate\Http\Response
 */
public function delete(RequestInterface $request)
{
    /** @var User $record */
    $record = $request->getRecord();
    $record->delete();
    
    return $this->reply()->noContent();
}
```
