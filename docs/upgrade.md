# Upgrade Guide

## 1.0.0-rc.* to ^1.0

No changes are required to upgrade.

## 1.0.0-beta.6 to 1.0.0-rc.1

### Config

We have re-implemented content-negotiation so that you can support non-JSON API media types at
runtime. As part of this change we've made a slight change to the API config to make it clearer
what the config sets.

Currently your API's content negotiation config looks like this:

```php
return [
    // ...
    
    /*
    |--------------------------------------------------------------------------
    | Content Negotiation
    |--------------------------------------------------------------------------
    |
    | This is where you register how different media types are mapped to
    | encoders and decoders. Encoders do the work of converting your records
    | into JSON API resources. Decoders are used to convert incoming request
    | body content into objects.
    |
    | If there is not an encoder/decoder registered for a specific media-type,
    | then an error will be sent to the client as per the JSON-API spec.
    |
    */
    'codecs' => [
        'encoders' => [
            'application/vnd.api+json',
        ],
        'decoders' => [
            'application/vnd.api+json',
        ],
    ],
];
```

You will need to change it to this:

```php
return [
    // ...
    

    /*
    |--------------------------------------------------------------------------
    | Encoding Media Types
    |--------------------------------------------------------------------------
    |
    | This defines the JSON API encoding used for particular media
    | types supported by your API. This array can contain either
    | media types as values, or can be keyed by a media type with the value
    | being the options that are passed to the `json_encode` method.
    |
    | These values are also used for Content Negotiation. If a client requests
    | via the HTTP Accept header a media type that is not listed here,
    | a 406 Not Acceptable response will be sent.
    |
    | If you want to support media types that do not return responses with JSON
    | API encoded data, you can do this at runtime. Refer to the
    | Content Negotiation chapter in the docs for details.
    |
    */
    'encoding' => [
        'application/vnd.api+json',
    ],

    /*
    |--------------------------------------------------------------------------
    | Decoding Media Types
    |--------------------------------------------------------------------------
    |
    | This defines the media types that your API can receive from clients.
    | This array is keyed by expected media types, with the value being the
    | service binding that decodes the media type.
    |
    | These values are also used for Content Negotiation. If a client sends
    | a content type not listed here, it will receive a
    | 415 Unsupported Media Type response.
    |
    | Decoders can also be calculated at runtime, and/or you can add support
    | for media types for specific resources or requests. Refer to the
    | Content Negotiation chapter in the docs for details.
    |
    */
    'decoding' => [
        'application/vnd.api+json',
    ],
];
```

### Routing

We have made changes to the routing to introduce a fluent syntax for defining routes. This will
not affect your application unless you type-hinted the `Routing\ApiGroup` class in any of your
route definitions. You will now need to type-hint `Routing\RouteRegistrar` instead.

Change this:

```php
use CloudCreativity\LaravelJsonApi\Routing\ApiGroup;

JsonApi::register('v1', [], function (ApiGroup $api) {
    // ...
});
```

to this:

```php
use CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar;

JsonApi::register('v1', [], function (RouteRegistrar $api) {
    // ...
});
```

### Controllers

#### Eloquent

The `Http\Controllers\EloquentController` class has been removed. This has been deprecated for
some time, and had no code in it. You can extend `Http\Controllers\JsonApiController` directly.

#### Searching Hook

As before the `searching` hook now occurs *before* records are queried with the resource's adapter.
We have added a `searched` hook that is invoked *after* records are returned by the adapter. This
hook receives the search results as its first argument.

You probably do not need to make any changes, unless the new `searched` hook is more useful to you
than the `searching` hook.

#### Reading Hook

The `reading` hook is now executed *before* the resource's adapter is called. Previously it was
invoked *after* the adapter. The first argument of this hook remains the record that is being read.

We have added a `didRead` hook that is executed *after* the resource's adapter is called. Its first
argument is the result returned by the adapter. This will usually be the record being read, but may
be `null` if the client provided any filter parameters and the record does not match those filters.

If you have implemented the `reading` hook on any of your controllers, and you intend it to always
receive the record that the request relates to, you do **not** need to make any changes. If you intend
the hook to receive the record that will be in the response, you should change the hook to `didRead`
and ensure the code handles the record being `null`.

#### Type-Hinting

We have changed the type-hinting of some protected methods so that they now type-hint the concrete
instance of `ValidatedRequest`. This will only affect your application if you have overloaded any
of the protected methods.

### Adapters

If any of your adapters were extending `Store\EloquentAdapter`, you now need to extend
`Eloquent\AbstractAdapter` instead.

We have removed deprecated properties and methods from the Eloquent adapter. A lot of these have
been deprecated for some time, so are unlikely to affect your application unless you have Eloquent
adapters that have not been changed for some time. If in doubt, check the changelog that lists
the removals.

### Validators

> The changes listed here are unlikely to affect most applications.

If you have implemented the `Contracts\Validation\ValidatorFactoryInterface` interface rather than 
extending our `Validation\AbstractValidators` class, you will need to add the `delete()` method
to your implementation.

In our `Validation\AbstractValidators` class we have renamed the following two protected methods:
- `createData` is now `dataForCreate`.
- `updateData` is now `dataForUpdate`.
- `relationshipData` is now `dataForRelationship`.
- `createResourceValidator` is now `validatorForResource`.
- `createQueryValidator` is now `validatorForQuery`.
  
We have also simplified our validator classes into a single class. The following classes have
been removed:
- `Validation\AbstractValidator`: use `Factories\Factory::createValidator()` or extend `Validation\Validator`.
- `Validation\ResourceValidator`: use `Factories\Factory::createResourceValidator()`.
- `Validation\QueryValidator`: use `Factories\Factory::createQueryValidator()`.

### Packages (Resource Providers)

The method signature for the `mount` method on the `AbstractProvider` class has changed to this:

```php
/**
 * Mount routes onto the provided API.
 *
 * @param \CloudCreativity\LaravelJsonApi\Routing\RouteRegistrar $api
 * @return void
 */
public function mount(RouteRegistrar $api): void
{
    //
}
```

> If you were previously using the second argument, check out the new documentation for adding
custom routes to the API in the [Routing chapter.](./basics/routing.md)

We have also added PHP 7 type-hinting to all other methods on the provider. (There were not many of them,
so as we were changing the signature of one method, it made sense to change all.)

## 1.0.0-alpha.4 to 1.0.0-beta.6

View [beta upgrade notes here.](https://github.com/cloudcreativity/laravel-json-api/blob/v1.0.0-beta.6/docs/upgrade.md)

## 1.0.0-alpha.* to 1.0.0-alpha.4

View [alpha upgrade notes here.](https://github.com/cloudcreativity/laravel-json-api/blob/v1.0.0-alpha.4/docs/upgrade.md)
