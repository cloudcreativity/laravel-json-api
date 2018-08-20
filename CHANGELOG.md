# Change Log
All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

### Added
- Package now supports Laravel 5.4 to 5.7 inclusive.
- [#210](https://github.com/cloudcreativity/laravel-json-api/issues/210)
Can now map a single JSON API path to multiple Eloquent eager load paths.
- Filtering Eloquent resources using the `id` filter is now also supported on to-many relationships.

### Changed
- [#184](https://github.com/cloudcreativity/laravel-json-api/issues/184)
Eloquent route keys are now used as the resource id by default.

### Fixed
- [#185](https://github.com/cloudcreativity/laravel-json-api/issues/185)
Rename adapter `store` method to `getStore` to avoid collisions with relation methods.
- [#187](https://github.com/cloudcreativity/laravel-json-api/issues/187)
Ensure hydration of Eloquent morph-many relationship works.
- [#194](https://github.com/cloudcreativity/laravel-json-api/issues/194)
Ensure encoding parameters are validated when reading a specific resource.
- Exception messages are no longer pushed into the JSON API error detail member.
- [#219](https://github.com/cloudcreativity/laravel-json-api/issues/219)
Can now use the `id` filter as one of many filters, previously the `id` filter ignored any
other filters provided.

## [1.0.0-alpha.4] - 2018-07-02

### Added
- [#203](https://github.com/cloudcreativity/laravel-json-api/issues/203)
JSON API container now checks whether there is a Laravel container binding for a class name. This
allows schemas, adapters etc to be bound into the container rather than having to exist as actual
classes.

### Fixed
- [#202](https://github.com/cloudcreativity/laravel-json-api/issues/202) 
When appending the schema and host on a request, the base URL is now also appended. This caters
for Laravel applications that are served from host sub-directories.

## [1.0.0-alpha.3] - 2018-05-17

### Added
- Errors that occur *before* a route is processed by a JSON API are now sent to the client as JSON API
error responses if the client wants a JSON API response. This is determined using the `Accept` header
and means that exceptions such as the maintenance mode exception are correctly returned as JSON API errors
if that is what the client wants.
- Can now override the default API name via the JSON API facade.

### Changed
- Field guarding that was previously available on the Eloquent adapter is now also available on the
generic adapter.
- Extracted the logic for an Eloquent `hasManyThrough` relation into its own relationship adapter (was
previously in the `has-many` adapter).
- Moved the `FindsManyResources` trait from the `Store` namespace to `Adapter\Concerns`.
- The `hydrateRelationships` method on the `AbstractResourceAdapter` is no longer abstract as it now
contains the implementation that was previously on the Eloquent adapter.
- The test exception handler has been moved from the dummy app to the `Testing` namespace. This means it
can now be used when testing JSON API packages.
- Merged the two resolvers provided by this package into a single class.
- [#176](https://github.com/cloudcreativity/laravel-json-api/issues/176)
When using *not-by-resource* resolution, the type of the class is now appended to the class name. E.g. 
`App\JsonApi\Adapters\PostAdapter` is now expected instead of `App\JsonApi\Adapters\Post`. The previous
behaviour can be maintained by setting the `by-resource` config option to the string `false-0.x`.
- The constructor dependencies for the `Repositories\ErrorRepository` have been simplified.

### Fixed
- Resolver was not correctly classifying the resource type when resolution was not by resource.
- [#176](https://github.com/cloudcreativity/laravel-json-api/issues/176)
Do not import model class in Eloquent adapter stub to avoid collisions with class name when using the legacy
*not-by-resource* behaviour.
- An exception is no longer triggered when create JSON API responses when there is no booted JSON API handling
the request.
- [#181](https://github.com/cloudcreativity/laravel-json-api/issues/181) Send a `419` error response with an
error object for a `TokenMismatchException`.
- [#182](https://github.com/cloudcreativity/laravel-json-api/issues/182) Send a `422` error response with
JSON API error objects when a `ValidationException` is thrown outside of JSON API validation.

### Deprecated
- The `report` method on the JSON API service/facade will be removed by `1.0.0`.

## [1.0.0-alpha.2] - 2018-05-06

### Added
- New authorizer interface and an abstract class that better integrates with Laravel's authentication and
authorization style. See the new [Security chapter](./docs/basics/security.md) for details.
- Can now generate authorizers using the `make:json-api:authorizer` command, or the `--auth` flag when
generating a resource with `make:json-api:resource`.
- The JSON API controller now has the following additional hooks:
  - `searching` for an *index* action.
  - `reading` for a *read* action.
- [#163](https://github.com/cloudcreativity/laravel-json-api/issues/163)
Added relationship hooks to the JSON API controller.

### Changed
- Generating an Eloquent schema will now generate a class that extends `SchemaProvider`, i.e. the generic schema.
- Existing JSON API controller hooks now receive the whole validated JSON API request rather than just the resource
object submitted by the client.

### Removed
- The previous authorizer implementation has been removed in favour of the new one. The following were deleted:
  - `Contract\Authorizer\AuthorizerInterface`
  - `Authorizer\AbstractAuthorizer`
  - `Authorizer\ReadOnlyAuthorizer`
  - `Exceptions\AuthorizationException`

### Deprecated
- Eloquent schemas are now deprecated in favour of using generic schemas. This is because of the amount of
processing involved without any benefit, as generic schemas are straight-forward to construct. The following
classes/traits are deprecated:
  - `Eloquent\AbstractSchema`
  - `Eloquent\SerializesModels`
  - `Schema\CreatesLinks`
  - `Schema\EloquentSchema` (was deprecated in `1.0.0-alpha.1`).

## [1.0.0-alpha.1] - 2018-04-29

As we are now only developing JSON API within Laravel applications, we have deprecated our framework agnostic 
`cloudcreativity/json-api` package. All the classes from that package have been merged into this package and
renamed to the `CloudCreativity\LaravelJsonApi` namespace. This will allow us to more rapidly develop this
Laravel package and simplify the code in subsequent releases.

### Added
- New Eloquent relationship adapters allows full support for relationship endpoints.
- Message bags can now have their keys mapped and/or dasherized when converting them to JSON API errors 
in the `ErrorBag` class.
- JSON API resource paths are now automatically converted to model relationship paths for eager loading in
the Eloquent adapter.
- The Eloquent adapter now applies eager loading when reading or updating a specific resource.
- Eloquent adapters can now *guard* JSON API fields via their `$guarded` and `$fillable` properties. These
are used when filling attributes and relationships.
- Added standard serialization of relationships within Eloquent schemas. This always serializes `self` and
`related` links for listed model relationships, and only adds the relationship `data` if the relationship is
being included in a compound document.

### Changed
- By default resources no longer need to have a controller as the generic JSON API controller will now
handle any resource. If resources have controllers, the `controller` routing option can be set to a string
controller name, or `true` to use a controller with the same name as the resource.
- Split adapter into resource and relationship adapter, and created classes to specifically deal with Eloquent
relationships.
- Adapters now handle both reading and modifying domain records.
- Moved Eloquent JSON API classes into a single namespace.
- Moved logic from Eloquent controller into the JSON API controller as the logic is no longer specific to
handling resources that related to Eloquent models.
- Filter, sort and page query parameters are no longer allowed for requests on primary resources (create, read
update and delete) because these query parameters do not apply to these requests.
- When serializing Eloquent models, if no attributes are specified for serialization (a `null` value), only
`Model::getVisible()` will now be used to work out what attributes must be serialized. Previously if `getVisible`
returned an empty array, `getFillable` would be used instead.

### Removed
- Delete Eloquent hydrator class as all hydration is now handled by adapters instead.
- The utility `Fqn` class has been removed as namespace resolution is now done by resolvers.
- The deprecated `Str` utility class has been removed. Use `CloudCreativity\JsonApi\Utils\Str` instead.

### Deprecated
- The Eloquent controller is deprecated in favour using the JSON API controller directly.
- The `Schema\EloquentSchema` is deprecated in favour of using the `Eloquent\AbstractSchema`.
- The `Store\EloquentAdapter` is deprecated in favour of using the `Eloquent\AbstractAdapter`.
- The `Testing\InteractsWithModels` trait is deprecated in favour of Laravel database assertion helpers.
- The `ErrorBag::toArray` method is deprecated in favour of `ErrorBag::all`.
- The `Schema\CreatesEloquentIdentities` trait is deprecated.

### Fixed
- [#128](https://github.com/cloudcreativity/laravel-json-api/issues/128) 
Filter, sort and page parameters validation rules are excluded for resource requests for which those
parameters do not apply (create, read, update and delete).
- [#92](https://github.com/cloudcreativity/laravel-json-api/issues/92)
Last page link is now excluded if there are pages, rather than linking to page zero.
- [#67](https://github.com/cloudcreativity/laravel-json-api/issues/67)
Pagination meta will no longer leak into error response if error occurs when encoding data.
- [#111](https://github.com/cloudcreativity/laravel-json-api/issues/111)
Sending an invalid content type header now returns a JSON API error object.
- [#146](https://github.com/cloudcreativity/laravel-json-api/issues/146)
Return a 404 JSON API error object and allow this to be overridden.
- [#155](https://github.com/cloudcreativity/laravel-json-api/issues/155)
Return a JSON API error when the request content cannot be JSON decoded.
- [#169](https://github.com/cloudcreativity/laravel-json-api/issues/169)
Generating a resource when the `by-resource` option was set to `false` had the wrong class name in the generated file.
