# Upgrade Guide

This file provides notes on how to upgrade between versions.

## v0.4 to v0.5

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

### Hydrators

The namespace of the `AbstractHydrator` has changed from `CloudCreativity\LaravelJsonApi\Hydrator\AbstractHydrator`
to `CloudCreativity\JsonApi\Hydrator\AbstractHydrator`.

### Validator Providers

The classes that provide validators for individual resource types generally extend `AbstractValidatorProvider`. This
now receives the resource type that is being validated into its public methods, and this is now passed down through 
the internal methods. You'll therefore need to make the changes described below.

This change has been made so that a single validator provider can be used for multiple resource types if desired. In
general you are likely to keep a validator provider per resource type (because attribute and relationship rules will
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

### Custom Validator Classes

If you have written any custom validator classes, you will need to refer to the `v0.5` to `v0.6` notes here: 
[cloudcreativty/json-api Upgrade Notes](https://github.com/cloudcreativity/json-api/blob/feature/v0.6/UPGRADE.md)

By custom validators, we mean the validators that validate individual parts of a JSON API document. You are unlikely
to have done this!

## v0.3 to v0.4

This was a substantial reworking of the package based on our experience of using it in production environments.
You'll need to refactor your implementation, referring to the wiki documentation when we complete that.
Apologies if this is a lot of work, however we think this package has significantly improved. We're now on the 
path to v1.0 and we'll keep breaking changes to a minimum from this point onwards.
