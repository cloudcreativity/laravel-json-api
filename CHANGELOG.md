# Change Log
All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

### Added
- [#247](https://github.com/cloudcreativity/laravel-json-api/issues/247) 
New date time rule object to validate a JSON date string is a valid ISO 8601 date and time format.
- [#246](https://github.com/cloudcreativity/laravel-json-api/issues/246)
Can now disable providing the existing resource attributes to the resource validator for an update
request.
- Added an `existingRelationships` method to the abstract validators class. Child classes can overload
this method if they need the validator to have access to any existing relationship values for an
update request.

### Changed
- [#248](https://github.com/cloudcreativity/laravel-json-api/pull/248)
Adapters now receive the JSON API document (HTTP content) as an array.
- Renamed `Http\Requests\IlluminateRequest` to `Http\Requests\JsonApiRequest`.

### Fixed
- [#201](https://github.com/cloudcreativity/laravel-json-api/issues/201)
Adapters now receive the array that has been transformed by Laravel's middleware, e.g. trim strings.

### Removed
- The deprecated `Contracts\Store\AdapterInterface` was removed. Use 
`Contracts\Adapter\ResourceAdapterInterface` instead.
- The deprecated `Adapter\HydratesAttributesTrait` was removed.
- The `Contracts\Http\Requests\RequestInterface` was removed as it is no longer necessary (because the
package is no longer framework-agnostic).

### Deprecated
- All interfaces in the `Contracts\Object` namespace will be removed for `2.0`.
- All classes in the `Object` namespace will be removed for `2.0`.

## [1.0.0-beta.5] - 2018-10-13

### Fixed
- [#240](https://github.com/cloudcreativity/laravel-json-api/issues/240)
Ensure PHP class name is passed to authorizer when authorizing fetch-many and create resource requests.

## [1.0.0-beta.4] - 2018-10-11

### Added
- New validation implementation that uses Laravel validators throughout. See the
[validation documentation](./docs/basics/validators.md) for details.
- Errors for the new validation implementation now have their `title`, `detail` and `code` members
translated via Laravel's translator. This means consuming applications can change the messages
by overriding the default translations provided by this package.
- [#234](https://github.com/cloudcreativity/laravel-json-api/issues/234)
HTTP exception headers are now added to the JSON API response when converting to JSON API errors.

### Changed
- Minimum PHP version is now `7.1`.
- Minimum Laravel version is now `5.5`.
- The validated request object is now abstract, with a concrete implementation for each type
of request.
- [#237](https://github.com/cloudcreativity/laravel-json-api/issues/237)
The route key name is now used as the default cursor key name.

### Removed
- The `Auth\HandlesAuthorizers` trait was removed and its logic moved into the `Authorize` middleware.

### Deprecated
- The previous validation solution is deprecated in favour of the new solution and will be removed at `2.0`.
- The error factory implementation is deprecated in favour of using Laravel translation and will be removed at `2.0`.
- The `Document\Error` and `Contracts\Document\MutableErrorInterface` are deprecated and will be removed
at `2.0`. You should use the error interface/class from the `neomerx/jsonapi` package instead.
- The following utility classes/traits/interfaces are deprecated and will be removed at `2.0`:
  - `Utils/ErrorCreatorTrait`
  - `Utils/ErrorsAwareTrait` and `Contracts\Utils\ErrorsAwareInterface`
  - `Utils/Pointers`
  - `Utils/Replacer` and `Contracts\Utils\ReplacerInterface`
- The `Contracts\Factories\FactoryInterface` is deprecated and will be removed at `1.0`. 
You should type-hint `Factories\Factory` directly instead.

## [1.0.0-beta.3] - 2018-09-21

### Added
- New cursor-based paging strategy, refer to the [Pagination docs](./docs/fetching/pagination.md) for
details.
- Refactored the client interface and implementation, improving record serializing and adding missing
relationship request methods.
- [#123](https://github.com/cloudcreativity/laravel-json-api/issues/123)
Can now use a custom resolver if wanting to override the default namespace resolution provided by
this package. This is documented in the [Resolvers chapter.](./docs/features/resolvers.md)

### Changed
- Extract model sorting from the Eloquent adapter into a `SortsModels` trait.
- [#144](https://github.com/cloudcreativity/laravel-json-api/issues/144)
Improved the helper method that creates new client instances so that it automatically adds a base
URI for the client if one is not provided.

### Fixed
- [#222](https://github.com/cloudcreativity/laravel-json-api/issues/222)
Adding related resources to a has-many relationship now does not add duplicates. The JSON API
spec states that duplicates must not be added, but the default Laravel behaviour does add duplicates.
The `HasMany` relationship now takes care of this by filtering out duplicates before adding them.

### Deprecated
- The following methods on the Eloquent adapter will be removed in `1.0.0` as they are no longer required:
  - `extractIncludePaths`
  - `extractFilters`
  - `extractPagination`
  - `columnForField`: use `getSortColumn` instead.

## [1.0.0-beta.2] - 2018-08-25

### Added
- Can now use Eloquent query builders as resource relationships using the `queriesOne` or `queriesMany`
JSON API relations.
- Custom relationships can now extend the `Adapter\AbstractRelationshipAdapter` class.

### Changed
- JSON API document is now only parsed out of the request if data is expected within the document.
This ensures that Laravel's get JSON test helpers can be used.
- Extracted common Eloquent relation querying methods to the `Eloquent\Concerns\QueriesRelations` trait.

### Deprecated
- The `Eloquent\AbstractRelation` class is deprecated and will be removed in `1.0.0`. Use the new
`Adapter\AbstractRelationshipAdapter` class and apply the `QueriesRelations` trait.
- The `Adapter\HydratesAttributesTrait` is deprecated as it is no longer in use and will be removed in
`1.0.0`.

## [1.0.0-beta.1] - 2018-08-22

### Added
- Package now supports Laravel 5.4 to 5.7 inclusive.
- [#210](https://github.com/cloudcreativity/laravel-json-api/issues/210)
Can now map a single JSON API path to multiple Eloquent eager load paths.
- [#218](https://github.com/cloudcreativity/laravel-json-api/issues/218)
Can now filter a request for a specific resource, e.g. `/api/posts/1?filter['published']=1`.
- Filtering Eloquent resources using the `id` filter is now also supported on to-many and to-one relationships.
- Can now set default sort parameters on an Eloquent adapter using the `$defaultSort` property.

### Changed
- [#184](https://github.com/cloudcreativity/laravel-json-api/issues/184)
Eloquent route keys are now used as the resource id by default.

### Removed
- The following deprecated methods have been removed from the Eloquent adapter:
  - `first`: use `searchOne` instead.

### Deprecated
- The follow methods are deprecated on the Eloquent adapter and will be removed in `1.0.0`:
  - `queryRelation`: use `queryToMany` or `queryToOne` instead.

### Fixed
- [#185](https://github.com/cloudcreativity/laravel-json-api/issues/185)
Rename adapter `store` method to `getStore` to avoid collisions with relation methods.
- [#187](https://github.com/cloudcreativity/laravel-json-api/issues/187)
Ensure hydration of Eloquent morph-many relationship works.
- [#194](https://github.com/cloudcreativity/laravel-json-api/issues/194)
Ensure encoding parameters are validated when reading a specific resource.
- Exception messages are no longer pushed into the JSON API error detail member, unless the Exception is a
HTTP Exception.
- [#219](https://github.com/cloudcreativity/laravel-json-api/issues/219)
Can now use the `id` filter as one of many filters, previously the `id` filter ignored any
other filters provided. It now also respects sort and paging parameters.

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
