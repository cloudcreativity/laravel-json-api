# Resolvers

This package uses your API's `namespace` and `by-resource` configuration settings to automatically
detect the fully-qualified name of your JSON API classes. This is described in the 
[API configuration chapter](../basics/api.md).

We work out the fully qualified name using a *resolver*. The strings that the resolver returns
are then checked for whether either the class exists or whether there is a binding in the service
container for the given name.

The resolver is also used when using the package's generators, as we ask the resolver for the
fully qualified namespace of the class that we are generating.

Our default implementation will work for the majority of applications. However we do recognise that
developers may prefer a different namespacing pattern than our implementation. This chapter therefore
describes how to replace our implementation with your own.

## Writing a Resolver

A resolver is a class that implements our 
`CloudCreativity\LaravelJsonApi\Contracts\Resolver\ResolverInterface`. 
To help you write your own implementation we also provide an abstract class that you can extend.
We recommend extending the abstract resolver.

In this example, we will use an application that has decided to organiser its namespaces in modules.
It may have a `User` module that had the following structure:

```
- app/Modules
  - User
    - Models
      - User.php
    - Web
      - Controllers
        - ...
      - Requests
        - ...
  - Post
    ...
``` 

In such a structure, we may want to store our JSON API classes in each module, under an `Api` namespace.
For example:

```
- app/Modules
  - User
    - Api
      - Adapter.php
      - Schema.php
      - ...
```

The following resolver would implement this strategy:

```php
<?php

namespace App\Modules;

use CloudCreativity\LaravelJsonApi\Resolver\AbstractResolver;

class ModuleResolver extends AbstractResolver
{
    
    /**
     * @param string $unit
     * @param $resourceType
     * @return string
     */
    protected function resolve($unit, $resourceType)
    {
        $module = ucfirst(str_singular($resourceType));
        
        return "App\\Modules\\{$module}\\Api\\{$unit}";
    }
    
}
```

The `resolve` method receive the unit type that it is resolving and the JSON API resource type name.
The unit type can be:

- `Adapter`
- `Authorizer`
- `Schema`
- `Validators`

The above implementation would therefore return `App\Modules\User\Api\Adapter` when the unit is
`Adapter` and the resource type is `users`.

When returning a string from the `resolve` method you do not need to test whether it
exists. The purpose of the method is to return the *expected* class name or container binding name.

> Although resolvers can return container binding names rather than class names, this will mean
the generators will not work.

## Using Resolvers

### Without a Factory

If you do not need to access any configuration for your API when creating your resolver,
you can use your resolver by adding its fully qualified class name (or a container binding
name) to your API's `resolver` configuration setting.

For example:

```php
// config/json-api-v1.php

return [
    'resolver' => \App\Modules\ModuleResolver::class,
    
    // ...
];
```

### Via a Factory

If you need access to your API's configuration when creating a resolver, or if you need
to calculate any resolver settings, you can create the resolver via a factory. For example,
if you have extended our abstract resolver you will need to provide the `resources` value
from the config when constructing your resolver.

The factory is an invokable class that receives the API name and the API's config. For 
the above example, our factory would be:

```php
namespace App\Modules;

class CreateModuleResolver
{
    
    public function __invoke($apiName, array $config)
    {
        return new ModuleResolver($config['resources']);
    }
}

```

> Resolver factories are constructed via the service container so you can use constructor injection
for any dependencies.

Then all we would need to do is change the `resolver` value in our API's configuration so that
it uses our factory:

```php
// config/json-api-v1.php

return [
    'resolver' => \App\Modules\CreateModuleResolver::class,
    
    // ...
];
```

## Controllers

It is worth noting that controller names are not detected via the resolver. This is because Laravel
routing uses the `namespace` route group option, and all JSON API routes are implemented using route
groups. More details on group namespaces can be found in the
[Laravel routing documentation](https://laravel.com/docs/routing#route-group-namespaces).

For our example above, our routing might look like this:

```php
// routes/api.php
// Assumes the namespace in our RouteServiceProvider is `App\Modules`.

JsonApi::register('default', [], function ($api) {
    Route::namespace('User\Api\Controllers')->group(function () use ($api) {
        // App\Modules\User\Api\Controllers\UserController
        $api->resource('users', ['controller' => true]);
        $api->resource('user-profiles');
    });
    
    Route::namespace('Posts\Api\Controllers')->group(function () use ($api) {
        $api->resource('posts');
        // App\Modules\Posts\Api\Controllers\PostCommentsController
        $api->resource('comments', ['controller' => 'PostCommentsController']);
    });
});
```

Remember that if no controller is specified, the package's `JsonApiController` will be used. For
more details, see the [controllers chapter](../basics/controllers.md).
