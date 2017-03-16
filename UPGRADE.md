# Upgrade Guide

This file provides notes on how to upgrade between versions.

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
