# Upgrade Guide

This file provides notes on how to upgrade between versions.

## v0.4 to v0.5

The dependent framework-agnostic package has a number of changes. Below lists the changes you will need to make 
to your application. However, if you are also extending underlying package functionality you will also need to refer 
to the `v0.5` to `v0.6` upgrade notes here: 
[cloudcreativty/json-api Upgrade Notes](https://github.com/cloudcreativity/json-api/blob/feature/v0.6/UPGRADE.md)

### Config

Paging config is now defined on a per-API basis, so you need to move your paging config into your API namespace
config in the `json-api.php` config file. We have updated the comment blocks to reflect this; you can find those
in the `config/json-api.php` file within this package.

Before:

``` php
return [
  'namespaces' => [
    'v1' => [
      'url-prefix' => '/api/v1',
      'supported-ext' => null,
    ],
  ],
  
  // ...
  
  'pagination' => [
    'params' => [
      'page' => null,
      'per-page' => null,
    ],
    'meta' => [
      'key' => null,
      'current-page' => 'page',
      'per-page' => 'size',
      'first-item' => null,
      'last-item' => null,
      'total' => null,
      'last-page' => 'last',
    ],
  ]
];
```

After:

``` php
return [
  'namespaces' => [
    'v1' => [
      'url-prefix' => '/api/v1',
      'supported-ext' => null,
      'paging' => [
        'page' => null,
        'per-page' => null,
      ],
      'paging-meta' => [
        'current-page' => 'page',
        'per-page' => 'size',
        'first-item' => null,
        'last-item' => null,
        'total' => null,
        'last-page' => 'last',
      ],
    ],
  ],
];
```

### Authorizers

#### Abstract Authorizer

If you are extending the `AbstractAuthorizer` class and implementing your own constructor, you will need to
inject an instance of `CloudCreativity\JsonApi\Contracts\Repositories\ErrorRepositoryInterface` into the parent
constructor. This change means you can now add errors to the error collection on your authorizer using the string
keys for error objects held within your `json-api-errors` config file. For example:

``` php
public function canRead($record, EncodingParametersInterface $parameters)
{
  if (!\Auth::check()) {
    $this->addError('access-denied');
    return false;
  }
  
  return true;
}
```

Note that the recommended way to create error objects is via the error repository (which holds your error config)
because it provides opportunities to add in translation etc of errors in the future.

You will also need to implement the `canModifyRelationship` method. This was previously implemented in the abstract
class but the previous implementation no longer works because of the change below.

#### Update Authorization

Update authorization can now access the resource sent by the client. At the point the authorizer is invoked, the
resource will have been validated to check that it complies with the JSON API spec but it will **not** have been
checked that it is valid according to your business logic - i.e. attributes and relationships will not have
been validated against the specific rules for that resource type.

Change the `canUpdate` method from this:

``` php
canUpdate($record, EncodingParametersInterface $parameters)
```

to:

``` php
// CloudCreativity\JsonApi\Contracts\Object\ResourceInterface;
canUpdate(
  $record, 
  ResourceInterface $recource, 
  EncodingParametersInterface $parameters
) 
```

#### Modify Relationship Authorization

Relationship modification authroization can now access the relationship sent by the client, as per the update 
authorization changes above.

Change the `canModifyRelationship` method from this:

``` php
public function canModifyRelationship(
    $relationshipKey,
    $record,
    EncodingParametersInterface $parameters
)
```

to:

``` php
// CloudCreativity\JsonApi\Contracts\Object\RelationshipInterface
public function canModifyRelationship(
    $relationshipKey,
    $record,
    RelationshipInterface $relationship,
    EncodingParametersInterface $parameters
)
```

### Controllers

#### Request Handler

The request class is no longer injected into the parent constructor. This change was necessary to support Laravel 5.3.
Instead you now need to implement the `getRequestHandler` method and return a string of the fully qualified class
name of the handler that is to be used. If you were not calling the parent constructor, you must now call it.

For example in an Eloquent controller, this:

``` php
  public function __construct(
      Posts\Request $request,
      Posts\Hydrator $hydrator,
      Posts\Search $search
  ) {
      parent::__construct(new Post(), $request, $hydrator, $search);
  }
```

Must be changed to:

``` php
public function __construct(Posts\Hydrator $hydrator, Posts\Search $search)
{
    parent::__construct(new Post(), $hydrator, $search);
}

protected function getRequestHandler()
{
    return Posts\Request::class;
}
```

#### Action Methods

The method signature of all the action methods have changed - they now all receive the validated JSON API request
object as they first argument.

For example, this:

``` php
public function read($resourceId) {}
```

Has become this:

``` php
use CloudCreativity\JsonApi\Contracts\Http\Requests\RequestInterface as JsonApiRequest;
// ...
public function read(JsonApiRequest $request) {}
```

Use the request object to get the resource id, relationship name and document (the JSON API request content). Refer
to the interface for methods available.

#### Search All

You now need to always inject a search object into the Eloquent controller, otherwise the controller will return a 
`501 Not Implemented` response for the index action.

Previously the controller would list all models and would allow a find-many request (i.e. the client providing
a list of ids as the `id` filter). If you have not written your own search class for the model being handled by
your controller and want to maintain the previous behaviour, inject a `SearchAll` instance via your constructor:

``` php
use CloudCreativity\LaravelJsonApi\Search\SearchAll;

public function __construct(SearchAll $search)
{
  parent::__construct(new Post(), null, $search);
}
```

### Hydrators

The namespace of the `AbstractHydrator` has changed from `CloudCreativity\LaravelJsonApi\Hydrator\AbstractHydrator`
to `CloudCreativity\JsonApi\Hydrator\AbstractHydrator`.

### Requests

Class `CloudCreativity\LaravelJsonApi\Http\Requests\AbstractRequest` has been removed. Instead you should extend
`CloudCreativity\JsonApi\Http\Requests\RequestHandler`.

Note that the constructor for this new class has the authorizer as the first argument and the validators as the 
second (i.e. it is the other way round from what it was before). We've made this change because authorization
occurs before validation, so it makes more sense for the arguments to be this way round.

### Responses (Reply Trait)

The method signature for the `errors` method has changed. To maintain the old behaviour use `error` instead (it
accepts an error collection as well as a single error). 

### Schemas

The JSON API specification recommends using hyphens for member names, e.g. `foo-bar`. The Eloquent schema now uses
this recommendation as its default behaviour. E.g. a model key of `foo_bar` will be serialized as `foo-bar`.

To maintain the old behaviour (of using the model key for the member key), set the `$hyphenated` property on your
schema to `false`. If you want to implement your own logic, overload the `keyForAttribute` method.

If you have irregular mappings of model keys to attribute keys, you can define these in your `$attributes` property,
e.g.

``` php
protected $attributes = [
  'foo',
  'bar',
  'foo_bar' // will be dasherized to 'foo-bar' by default
  'baz' => 'bat' // model key `baz` will be serialized to attribute key `bat`
];
```

### Search

We've merged the abstract sorted search class into the abstract search class, to simplify things.

You'll need to change:

``` php
use CloudCreativity\LaravelJsonApi\Search\AbstractSortedSearch;

class Search extends AbstractSortedSearch
{
}
```

to:

``` php
use CloudCreativity\LaravelJsonApi\Search\AbstractSearch;

class Search extends AbstractSearch
{
}
```

#### Constructor

If you are overloading the constructor, you will need to call the parent constructor and provide it with the 
following two dependencies:

```
CloudCreativity\JsonApi\Contracts\Http\HttpServiceInterface
CloudCreativity\JsonApi\Contracts\Pagination\PaginatorInterface
```

#### Sort Parameter Field Conversion

When working out what column to use for a sort parameter, the sort parameter will automatically be snake cased if
your model uses snake attributes. If it does not use snake attributes, the parameter will be camel cased.

If you have irregular mappings of search params to column names, add that mapping to your `$sortColumns` property,
e.g. `$sortColumns = ['foo-bar' => 'baz_bat']`.

If you want to use a completely different logic for converting search params to column names, overload the 
`columnForField()` method.

### Validator Providers

The classes that provide validators for individual resource types generally extend `AbstractValidatorProvider`. This
now receives the resource type that is being validated into its public methods, and this is now passed down through 
the internal methods. You'll therefore need to make the changes described below.

This change has been made so that a single validator provider can be used for multiple resource types if desired. 
Although you are likely to keep a validator provider per resource type (because attribute and relationship rules will
be specific to a resource type), this has given us the capability to implement a generic validator provider capable of
validating any resource type according to the JSON API spec.

#### Constructor

If you have implemented a constructor, you will need to type hint the following interface and pass it to the parent
constructor:

```
CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorFactoryInterface
```

#### Resource Type Property

You can remove the `$resourceType` property as it is no longer required.
 
#### Method Changes

You'll need to adjust the signature of the following abstract methods, from:
 
``` php
attributeRules($record = null)
relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
filterRules()
```

to:

``` php
attributeRules($resourceType, $record = null)
relationshipRules(RelationshipsValidatorInterface $relationships, $resourceType, $record = null)
filterRules($resourceType)
```

The `filterRules` method is actually no longer abstract, so if you are returning an empty array from it you can delete
it.

The signatures of other `protected` methods have also changed to pass down this additional argument. If you have
implemented any other methods, check the abstract class for the new argument order.

## v0.3 to v0.4

This was a substantial reworking of the package based on our experience of using it in production environments.
You'll need to refactor your implementation, referring to the wiki documentation when we complete that.
Apologies if this is a lot of work, however we think this package has significantly improved. We're now on the 
path to v1.0 and we'll keep breaking changes to a minimum from this point onwards.
