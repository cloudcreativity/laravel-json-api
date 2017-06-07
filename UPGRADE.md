# Upgrade Guide

This file provides notes on how to upgrade between versions.

## v0.8 to v0.9 (Unreleased)

### Resource IDs

The JSON API spec states that the `id` member for a resource MUST be a string. Previous versions of this package
were allowing integers as well as strings.

This has now been corrected, and validators will reject any resource objects or resource identfiers that have an
`id` member that is not a string. The JSON API error object generated clearly identifies the problem.

When upgrading, you might find that some of your tests will fail if you were writing a test for a model that
has an integer primary key. For example if you were doing this in your test:

```php
$data = [
  'type' => 'posts',
  'id' => $model->getKey(),
  'attributes' => [
    'title' => 'My First Post',    
  ],
  'relationships' => [
    'author' => [
      'data' => ['type' => 'users', 'id' => $model->author_id],
    ],
  ],
];
```

You will need to change it to this:

```php
$data = [
  'type' => 'posts',
  'id' => (string) $model->getKey(),
  'attributes' => [
    'title' => 'My First Post',    
  ],
  'relationships' => [
    'author' => [
      'data' => ['type' => 'users', 'id' => (string) $model->author_id],
    ],
  ],
];
```

If you have clients connecting to your server that have been following the JSON API spec, this change will not
affect them. For example, we use Ember and it always sends `id` members as strings. However, if you have
clients connecting that are not following the spec, they may start receiving `HTTP 400` responses if they 
send non-string `id` members.

### Class Name Changes

You will need to rename the following:

- `CloudCreativity\JsonApi\Contracts\Object\ResourceInterface` to `ResourceObjectInterface` (same namespace). 
This may affect your controllers, hydrators and validators.
- `CloudCreativity\JsonApi\Contracts\Object\StandardObjectInterface` to 
`CloudCreativity\Utils\Object\StandardObjectInterface`. This will affect hydrators.

If you have extended any of the internals of this package, you will need to check the full list of namespace changes
in the `cloudcreativity/json-api` upgrade guide (v0.8 to v0.9).

### HTTP Service / JSON API Service

The `CloudCreativity\JsonApi\Contracts\Http\HttpServiceInterface` has been removed. Use the `JsonApi` facade or
inject the `CloudCreativity\JsonApi\Services\JsonApiService` instance.

Note the deprecated `isActive` method has been removed from the JSON API service (facade). Use the `hasApi()` method
instead.

### Validation Error Factory

The `CloudCreativity\LaravelJsonApi\Contracts\Validators\ValidatorErrorFactoryInterface` has been removed. 
This is only likely to affect applications that have extended the internals of this package.
Change any type-hints or implementations of this to use the
`CloudCreativity\JsonApi\Contracts\Validators\ValidatorErrorFactoryInterface` instead. 

## v0.7 to v0.8

Refer to the [UPGRADE-0.8.md](UPGRADE-0.8.md) file for instructions.

## v0.6 to v0.7

### Eloquent Hydrator

The method signatures of `EloquentHydrator::deserializeAttribute()` and `EloquentHydrator::isDateAttribute()`,
have changed to add the record being hydrated as the final method argument. This will only affect hydrators
in which you have overloaded either of these methods.

## v0.5 to v0.6

### Config

Add the following to your `json-api-errors.php` config file:

```php
  /**
   * When the resource type of a related resource is not recognised.
   */
  V::RELATIONSHIP_UNKNOWN_TYPE => [
      Error::TITLE => 'Invalid Relationship',
      Error::DETAIL => "Resource type '{actual}' is not recognised.",
      Error::STATUS => 400,
  ],
```

### Other

This upgrade includes updating `cloudcreativity/json-api` from v0.6 to v0.7. This will not affect the vast majority
of applications. However, if you have implemented your own Store or Validator Error Factory, you will need to refer
to the upgrade notes in that package.

## v0.5.0|v0.5.1 to v0.5.2

Version `0.5.2` adds generator commands to your application. We've updated the configuration so you will need to
add the `generator` config array from the bottom of the `config/json-api.php` to the `json-api.php` config file
in your application. 

This however is optional as the generators will work without this configuration being added.

## v0.4 to v0.5

Refer to the [UPGRADE-0.5.md](UPGRADE-0.5.md) file for instructions.

## v0.3 to v0.4

This was a substantial reworking of the package based on our experience of using it in production environments.
You'll need to refactor your implementation, referring to the wiki documentation when we complete that.
Apologies if this is a lot of work, however we think this package has significantly improved. We're now on the 
path to v1.0 and we'll keep breaking changes to a minimum from this point onwards.
