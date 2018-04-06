# Controllers

This chapter describes how to implement your own controller for processing JSON API requests and returning JSON API
responses.

## Setup

In order to send JSON API responses, you will need to apply the `CreatesResponses` trait to your controller. Here is 
an example controller:

```php
namespace App\Http\Controllers\Api;

use CloudCreativity\LaravelJsonApi\Http\Controllers\CreatesResponses;
use Illuminate\Routing\Controller;
 
class UsersController extends Controller
{

    use CreatesResponses;
    
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
 * @param \CloudCreativity\LaravelJsonApi\Contracts\Store\StoreInterface $store
 * @param \CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestInterface $request
 * @return \Illuminate\Http\Response
 */
public function index(StoreInterface $store, RequestInterface $request)
{
    $records = $store->query(
        $request->getResourceType(),
        $request->getParameters()
    );
    
    return $this->reply()->content($records);
}
```

### Create

```php
/**
 * @param \App\JsonApi\Users\Hydrator $hydrator
 * @param \CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface $resource
 * @return \Illuminate\Http\Response
 */
public function create(Hydrator $hydrator, ResourceObjectInterface $resource)
{
    $record = new User();
    $hydrator->hydrate($resource, $record);

    // As an example, if we wanted to wrap the change in a transaction...
    \DB::transaction(function () use ($record) {
        $record->save();
    });

    return $this->reply()->created($record);
}
```

### Read

```php
/**
 * @param \App\User $record
 * @return \Illuminate\Http\Response
 */
public function read(User $record)
{
    return $this->reply()->content($record);
}
```

### Update

```php
/**
 * @param \App\JsonApi\Users\Hydrator $hydrator
 * @param \CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface $resource
 * @param \App\User $record
 * @return \Illuminate\Http\Response
 */
public function update(Hydrator $hydrator, ResourceObjectInterface $resource, User $record)
{
    $hydrator->hydrate($resource, $record);
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
 * @param \App\User $record
 * @return \Illuminate\Http\Response
 */
public function delete(User $record)
{
    $record->delete();
    
    return $this->reply()->noContent();
}
```

## Relationships

If you have defined relationships in the schema of a resource, you would then need to add the following actions to your 
controller:

1. `readRelatedResource()`
2. `readRelationship()`

### Read Related Resource
If you link one resource to another through relationship, you'll need this to read the related resource.

```php
/**
 * @param \CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestInterface $request
 * @param \App\User $record
 * @return \Illuminate\Http\Response
 */
public function readRelatedResource(RequestInterface $request, User $record)
{
    $key = $request->getRelationshipName();
    $method = 'get' . ucfirst($key);

    return $this
        ->reply()
        ->content(call_user_func([$record, $method]));
}
```

### Read Relationship

This is for reading the relationship between two resources.

```php
/**
 * @param \CloudCreativity\LaravelJsonApi\Contracts\Http\Requests\RequestInterface $request
 * @param \App\User $record
 * @return \Illuminate\Http\Response
 */
public function readRelationship(RequestInterface $request, User $record)
{
    $key = $request->getRelationshipName();
    $method = 'get' . ucfirst($key);
    
    return $this
        ->reply()
        ->relationship(call_user_func([$record, $method]));
}
```
