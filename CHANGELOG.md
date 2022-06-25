# Change Log

All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## [5.0.0-alpha.1] - 2022-06-25

### Changed

- **BREAKING** Upgraded the `neomerx/json-api` dependency from `v1` to `v5` of our fork
  `laravel-json-api/neomerx-json-api`. Refer to the [Upgrade Guide](./docs/upgrade.md) for details of the required
  changes.  

## [4.0.1] - 2022-04-24

### Fixed

- Fixed deprecation messages in tests originating from the `fakerphp/faker` package.

## [4.0.0] - 2022-02-09

### Added

- Package now supports PHP 8.1.
- Package now supports Laravel 9.

### Changed

- Minimum PHP version is now 7.4 (previously was 7.3).
- Minimum Laravel version is now 8.76. This is needed as we are dependent on all the Laravel PHP 8.1 changes.
- Package now depends on our fork of the Neomerx JSON:API package - `laravel-json-api/neomerx-json-api`. This is a
  non-breaking change.
- **BREAKING** Added return types to internal methods, to remove deprecation notices in PHP 8.1. This will affect your
  implementation if you have extended any of our classes and overloaded a method that now has a return type. 

### Removed

- **BREAKING** Removed the following classes from the `CloudCreativity\LaravelJsonApi\Testing` namespace. You must 
  use classes (with the same names) from the `LaravelJsonApi\Testing` namespace, after installing the 
  `laravel-json-api/testing` package as a dev dependency. Refer to the upgrade guide for details. Classes/traits removed
  are:
    - `MakesJsonApiRequests`
    - `TestBuilder`
    - `TestResponse`

## Unreleased

### Changed

- [#589](https://github.com/cloudcreativity/laravel-json-api/issues/589) Bind request API classes into the service
  container as singletons. This is considered non-breaking because HTTP requests are always handled by new instances of
  the Laravel application. Binding these classes as singletons is no different from Laravel binding its HTTP request
  class as a singleton.

## [3.3.0] - 2021-02-06

### Added

- [#586](https://github.com/cloudcreativity/laravel-json-api/pull/586) Added French translations for validation and
  specification compliance messages.
- [#490](https://github.com/cloudcreativity/laravel-json-api/issues/490) Add `newRelationQuery()` method to Eloquent
  adapter.

### Fixed

- [#576](https://github.com/cloudcreativity/laravel-json-api/issues/576) Correctly pass existing resource values to JSON
  values, before merging client values for validation.

## [3.2.0] - 2020-11-26

### Added

- Package now supports PHP 8.
- [#570](https://github.com/cloudcreativity/laravel-json-api/issues/570)
  Exception parser now handles the Symfony request exception interface.
- [#507](https://github.com/cloudcreativity/laravel-json-api/issues/507)
  Can now specify the resource type and relationship URIs when registering routes. This allows the URI fragment to be
  different from the resource type or relationship name.

### Fixed

- Fixed qualifying column for morph-to-many relations. This was caused by Laravel introducing a breaking change of
  adding a `qualifyColumn` method to the relation builder. Previously calling `qualifyColumn` on the relation forwarded
  the call to the model.

## [3.1.0] - 2020-10-28

### Added

- [#563](https://github.com/cloudcreativity/laravel-json-api/pull/563)
  Added Dutch translation files for validation and errors.

### Fixed

- [#566](https://github.com/cloudcreativity/laravel-json-api/issues/566)
  Ensure the exception parser correctly parses an instance of this package's `JsonApiException`.
- Exception parser now correctly uses the Symfony `HttpExceptionInterface` instead of the actual `HttpException`
  instance. Although this change would break consuming applications that have extended the `ExceptionParser` class, it
  is considered a bug fix as it should have been type-hinting the interface in `v3.0.0`.

## [3.0.1] - 2020-10-14

### Fixed

- [#560](https://github.com/cloudcreativity/laravel-json-api/pull/560)
  Fixed type-hinting in abstract adapter stub.

## [3.0.0] - 2020-09-09

### Added

- [#545](https://github.com/cloudcreativity/laravel-json-api/issues/545)
  The request test builder now supports testing requests that do not have JSON API request content, but expect a JSON
  API response. For example, a file upload that results in a JSON API resource in the response body can be tested using:
  `$this->jsonApi()->asMultiPartFormData()->withPayload($data)->post('/api/v1/avatars');`.

### Changed

- Minimum PHP version is now `7.3`.
- Minimum Laravel version is now `8.0`.
- [#497](https://github.com/cloudcreativity/laravel-json-api/issues/497) and
  [#529](https://github.com/cloudcreativity/laravel-json-api/pull/529)
  **BREAKING:** The method signature of the `AbstractValidators::rules()` method has changed, so that the method has
  access to the data that will be validated.
- [#393](https://github.com/cloudcreativity/laravel-json-api/issues/393)
  **BREAKING:** when using the `SoftDeletesModel` trait on an adapter, the expected JSON API field for the soft delete
  attribute now defaults to the camel-case version of the model column. For example, column `deleted_at` previously
  defaulted to the JSON API field `deleted-at`, whereas now it will default to `deletedAt`. To continue to use
  dash-case, set the `softDeleteField` property on your adapter.

## [2.2.0] - 2020-09-09

### Added

- [#549](https://github.com/cloudcreativity/laravel-json-api/issues/549)
  Can now add sort methods to an Eloquent adapter if sorting is more complex than just sorting by a column value.

### Fixed

- The error translator will now detect if the translated value is identical to the translation key path, and
  return `null` when it is. This fixes behaviour that changed in Laravel 7.28.

## [2.1.0] - 2020-09-04

### Added

- [#538](https://github.com/cloudcreativity/laravel-json-api/issues/538)
  New JSON API exception class that accepts the new error objects from this package. It is recommended that you
  use `CloudCreativity\LaravelJsonApi\Exceptions\JsonApiException`
  combined with the `CloudCreativity\LaravelJsonApi\Document\Error\Error`. It is not recommended to use
  the `Neomerx\JsonApi\Exceptions\JsonApiException` class as support for this exception class will be removed in a
  future version.

## [2.0.0] - 2020-06-17

### Added

- Translation files can now be published using the `vendor:publish` Artisan command.

### Fixed

- [#506](https://github.com/cloudcreativity/laravel-json-api/pull/506)
  Resolve model bindings correctly when substituting URL parameters.
- Updated type-hinting for `Responses::errors()` method and allowed a `null` default status code to be passed
  to `Helpers::httpErrorStatus()` method.
- [#518](https://github.com/cloudcreativity/laravel-json-api/issues/518)
  Ensure empty `sort` and `include` query parameters pass validation.

## [2.0.0-beta.3] - 2020-04-13

### Added

- [#503](https://github.com/cloudcreativity/laravel-json-api/issues/503)
  The JSON API `hasOne` relation now also supports an Eloquent `morphOne` relation.

## [2.0.0-beta.2] - 2020-04-12

### Changed

- Refactored API configuration to reduce constructor arguments in the API class.
- Updated UUID dependency so that version 3 and 4 are allowed.

### Fixed

- [#498](https://github.com/cloudcreativity/laravel-json-api/issues/498)
  Update exception parser interface to type-hint a `Throwable` instance instead of an
  `Exception`.

## [2.0.0-beta.1] - 2020-03-04

### Added

- [#348](https://github.com/cloudcreativity/laravel-json-api/issues/348)
  Can now use route parameters in the API's URL configuration value.
- New test builder class allows tests to fluently construct a test JSON API request.
- [#427](https://github.com/cloudcreativity/laravel-json-api/issues/427)
  Test requests will now ensure that query parameter values are strings, integers or floats. This is to ensure that the
  developer is correctly testing boolean filters.

### Changed

- Minimum PHP version is now `7.2`.
- Minimum Laravel version is now `7.0`.
- Amended the store interface so that it always takes a string resource type and string id, instead of the deprecated
  resource identifier object.
- Moved the `Validation\ErrorTranslator` class to `Document\Error\Translator`.
- The `Testing\MakesJsonApiRequests::jsonApi()` method no longer accepts any function arguments, and returns an instance
  of `Testing\TestBuilder`. This allows the developer to fluently execute test JSON API requests.

### Removed

- The deprecated `0.x` validation implementation was removed. This deletes all interfaces in the `Contracts\Validators`
  namespace, and classes in the `Validators` namespace. You should use
  the [documented validation implementation](./docs/basics/validators.md) instead.
- The deprecated `json_api_request()` helper was removed.
- The following methods were removed from the JSON API service (and are therefore no longer available via the facade):
  - `request()`: use `currentRoute()` instead.
  - `defaultApi()`: set the default API via `LaravelJsonApi::defaultApi()` instead.
- All deprecated methods on the `Testing\MakesJsonApiRequests` trait and `Testing\TestResponse` class were removed.
- Removed the `Http\Requests\ValidatedRequest::validate()` method, as Laravel replaced it with
  `validateResolved()`. This affects all JSON API request classes.
- Additionally, the following deprecated interfaces, classes and traits were removed:
  - `Api\ResourceProvider` - extend `Api\AbstractProvider` instead.
  - `Contracts\Document\MutableErrorInterface`
  - `Contracts\Exceptions\ErrorIdAllocatorInterface`
  - `Contracts\Factories\FactoryInterface`
  - `Contracts\Http\Responses\ErrorResponseInterface`
  - `Contracts\Object\*` - all interfaces in this namespace.
  - `Contracts\Repositories\ErrorRepositoryInterface`
  - `Contracts\Utils\ErrorReporterInterface`
  - `Contracts\Utils\ErrorsAwareInterface`
  - `Contracts\Utils\ReplacerInterface`
  - `Document\Error` - use `Document\Error\Error` instead.
  - `Eloquent\AbstractSchema` - extend the `neomerx/json-api` schema instead.
  - `Eloquent\Concerns\SerializesModels` trait.
  - `Exceptions\MutableErrorCollection`
  - `Exceptions\NotFoundException`
  - `Exceptions\RecordNotFoundException` - use `Exceptions\ResourceNotFoundException` instead.
  - `Http\Query\ChecksQueryParameters` trait.
  - `Http\Requests\JsonApiRequest`
  - `Http\Responses\ErrorResponse`
  - `Object\*` - all classes in this namespace.
  - `Repositories\ErrorRepository`
  - `Schema\AbstractSchema` - extend the `neomerx/json-api` schema instead.
  - `Schema\CreatesEloquentIdentities` trait.
  - `Schema\CreatesLinks` trait.
  - `Schema\EloquentSchema` - extend the `neomerx/json-api` schema instead.
  - `Utils\AbstractErrorBag`
  - `Utils\ErrorBag`
  - `Utils\ErrorCreatorTrait`
  - `Utils\ErrorsAwareTrait`
  - `Utils\Pointer`
  - `Utils\Replacer`

## [1.7.0] - 2020-04-13

### Added

- [#503](https://github.com/cloudcreativity/laravel-json-api/issues/503)
  The JSON API `hasOne` relation now also supports an Eloquent `morphOne` relation.

### Changed

- Minimum Laravel version is now `5.8` (previously `5.5`).

## [1.6.0] - 2020-01-13

### Added

- Updated to support PHP `7.4` (minimum PHP remains `7.1`).

### Changed

- [#440](https://github.com/cloudcreativity/laravel-json-api/pull/440)
  Amend query method signature on validated request to match Laravel request signature.

### Fixed

- [#445](https://github.com/cloudcreativity/laravel-json-api/issues/445)
  Allow resource identifier to be zero.
- [#447](https://github.com/cloudcreativity/laravel-json-api/issues/447)
  Ensure deserialized attributes do not include relations if the relation has been sent as an attribute.

## [1.5.0] - 2019-10-14

### Added

- [#415](https://github.com/cloudcreativity/laravel-json-api/issues/415)
  Added a has-one-through JSON API resource relationship for the Eloquent has-one-through relationship that was added in
  Laravel 5.8.

### Fixed

- [#439](https://github.com/cloudcreativity/laravel-json-api/issues/439)
  Fixed tests failing in Laravel `^6.1`. **Applications using Laravel 6 need to upgrade the JSON API testing package
  to `^2.0`** as follows:

```bash
$ composer require --dev cloudcreativity/json-api-testing:^2.0
```

## [1.4.0] - 2019-09-04

### Added

- Package now supports Laravel 6.
- [#333](https://github.com/cloudcreativity/laravel-json-api/issues/333)
  Eloquent adapters now have a `filterWithScopes()` method, that maps JSON API filters to model scopes and the
  Eloquent `where*` method names. This is opt-in: i.e. to use, the developer must call `filterWithScopes()` within their
  adapter's `filter()` method.
- Added `put`, `putAttr` and `putRelation` methods to the `ResourceObject` class.

## [1.3.1] - 2019-08-19

### Fixed

- Updated Travis config for Laravel `5.9` being renamed `6.0`.
- [#400](https://github.com/cloudcreativity/laravel-json-api/pull/400)
  Fix overridden variable in error translator.

## [1.3.0] - 2019-07-24

### Added

- [#352](https://github.com/cloudcreativity/laravel-json-api/issues/352)
  Can now set an API's default controller name to the singular version of the resource type.
- Can now register a callback for resolving a controller name from a resource type.
- [#373](https://github.com/cloudcreativity/laravel-json-api/issues/373)
  Can now change the JSON API controller's default connection and whether it uses transactions via an API's config.
- [#377](https://github.com/cloudcreativity/laravel-json-api/issues/377)
  Can now toggle the simple pagination strategy back to using length aware pagination.
- [#380](https://github.com/cloudcreativity/laravel-json-api/issues/380)
  Can now customise the conversion of a resource id to a database id on an adapter by overloading the
  `databaseId()` method.
- New `HasOne` and `HasMany` rule objects for validating that a relationship is *to-one* or
  *to-many*, and has the expected resource type(s).
- [#391](https://github.com/cloudcreativity/laravel-json-api/issues/391)
  Allow the validators `existingRelationships()` method to return the existing related records, and automatically
  convert these to JSON API identifiers.

### Fixed

- [#371](https://github.com/cloudcreativity/laravel-json-api/issues/371)
  Ensure Eloquent delete/deleting events are fired when soft-deleting a resource.
- Eloquent adapter hooks will now be invoked when force-deleting a resource that uses the `SoftDeletesModels` trait.

## [1.2.0] - 2019-06-20

### Added

- [#360](https://github.com/cloudcreativity/laravel-json-api/issues/360)
  Allow soft delete attribute path to use dot notation.
- Added `domain` method to API fluent routing methods.
- [#337](https://github.com/cloudcreativity/laravel-json-api/issues/337)
  Can now apply global scopes to JSON API resources via [adapter scopes.](./docs/basics/adapters.md#scopes)

### Fixed

- [#370](https://github.com/cloudcreativity/laravel-json-api/pull/370)
  Fix wrong validation error title when creating a custom validator.
- [#369](https://github.com/cloudcreativity/laravel-json-api/issues/369)
  Fix using an alternative decoding type for update (`PATCH`) requests.
- [#362](https://github.com/cloudcreativity/laravel-json-api/issues/362)
  Fix fatal error in route class caused by polymorphic entity types.
- [#358](https://github.com/cloudcreativity/laravel-json-api/issues/358)
  Queue listener could trigger a `ModelNotFoundException` when deserializing a job that had deleted a model during
  its `handle()` method.
- [#347](https://github.com/cloudcreativity/laravel-json-api/issues/347)
  Update `zend-diactoros` dependency.

## [1.1.0] - 2019-04-12

### Added

- [#315](https://github.com/cloudcreativity/laravel-json-api/issues/315)
  Allow developers to use the exact JSON API field name as the relationship method name on their adapters, plus for
  default conversion of names in include paths. Although we recommend following the PSR1 standard of using camel case
  for method names, this does allow a developer to use snake case field names with snake case method names.
- Exception handlers can now use the `parseJsonApiException()` helper method if they need to convert JSON API exceptions
  to HTTP exceptions. Refer to the [installation instructions](./docs/installation.md)
  for an example of how to do this on an exception handler.

### Fixed

- [#329](https://github.com/cloudcreativity/laravel-json-api/issues/329)
  Render JSON API error responses when a codec has matched, but the client has not explicitly asked for JSON API
  response (e.g. asked for `Accept: */*`).
- [#313](https://github.com/cloudcreativity/laravel-json-api/issues/313)
  Ensure that the standard paging strategy uses the resource identifier so that pages have a
  [deterministic sort order](https://tighten.co/blog/a-cautionary-tale-of-nondeterministic-laravel-pagination).

## [1.0.1] - 2019-03-12

### Fixed

- A `303 See Other` will now not be returned if a client job has failed. This is necessary because otherwise there is no
  way for an API client to determine that the job completed but failed.

## [1.0.0] - 2019-02-28

### Added

- Package now supports Laravel 5.8.

### Fixed

- [#302](https://github.com/cloudcreativity/laravel-json-api/issues/302)
  Reject resource objects sent in relationships, as the spec defines that only resource identifiers are expected. This
  is based on the object having either an `attributes` or a `relationships` member.
- [#295](https://github.com/cloudcreativity/laravel-json-api/issues/295)
  Use `null` for empty `from`/`to` fields in cursor page meta.

## [1.0.0-rc.2] - 2019-02-05

### Added

- [#265](https://github.com/cloudcreativity/laravel-json-api/issues/265)
  Allow wildcard media type parameters when matching decodings. Allows `multipart/form-data; boundary=*` to be accepted
  for decoding purposes.

## [1.0.0-rc.1] - 2019-01-30

### Added

- [#271](https://github.com/cloudcreativity/laravel-json-api/issues/271)
  Can now validate delete resource requests.
- [#196](https://github.com/cloudcreativity/laravel-json-api/issues/196)
  Can now add custom actions to resource controllers. Refer to the [Routing](./docs/basics/routing.md) and
  [Controllers](./docs/basics/controllers.md) chapters.
- [#242](https://github.com/cloudcreativity/laravel-json-api/issues/242)
  Can now override the default controller for an API. Refer to the [Controllers](./docs/basics/controllers.md)
  chapter for details.
- [#289](https://github.com/cloudcreativity/laravel-json-api/pull/289)
  Can now opt-in to Laravel validation failure data being added to the `meta` member of JSON API error objects.

### Changed

- [#254](https://github.com/cloudcreativity/laravel-json-api/pull/254)
  Refactored content negotiation so that multiple media types can be supported. Refer to the
  [Media Types](./docs/features/media-types.md) documentation for details.
- Simplified the validator classes into a single class: `Validators\Validator`.
- Renamed a lot of classes in the `Routing` namespace. They are also marked as `final` because they are not meant to be
  extended.
- Modified the abstract `mount` method that package providers use to add routes to an API. Also added PHP 7 type-hinting
  to all methods in the abstract class.

### Fixed

- [#265](https://github.com/cloudcreativity/laravel-json-api/issues/265)
  Allow wildcard media type parameters when matching decoders. This enables support for media types that include random
  parameter values, primarily the `multipart/form-data` type that has a `boundary` parameter that is unpredictable.
- [#280](https://github.com/cloudcreativity/laravel-json-api/issues/280)
  Validation error objects for relationship objects now have correct source pointers.
- [#284](https://github.com/cloudcreativity/laravel-json-api/issues/284)
  Content negotiation middleware no longer causes a container binding exception when the Kernel is terminated.

### Removed

- The following classes in the `Validation` namespace were removed as the `Validation\Validator`
  class can be used instead, or validators can be constructed via the factory instead:
  - `AbstractValidator`
  - `ResourceValidator`
  - `QueryValidator`
- The deprecated `EloquentController` was removed - extend `JsonApiController` directly.
- The `Store\EloquentAdapter` was removed - extend `Eloquent\AbstractAdapter` directly.
- The following previously deprecated methods/properties were removed from the `EloquentAdapter`:
  - public method `queryRelation()`: renamed `queryToMany()`.
  - protected property `$with`: renamed `$defaultWith`.
  - protected method `keyForAttribute()`: renamed `modelKeyForField()`.
  - protected method `columnForField()`: renamed `getSortColumn()`.
  - protected method `all()`: renamed `searchAll()`.
  - protected method `extractIncludePaths()`: overload the `getQueryParameters()` method instead.
  - protected method `extractFilters()`: overload the `getQueryParameters()` method instead.
  - protected method `extractPagination()`: overload the `getQueryParameters()` method instead.
- The previously deprecated `Eloquent\Concerns\AbstractRelation` class was removed.
  Extend `Adapter\AbstractRelationshipAdapter` and use the `Eloquent\Concerns\QueriesRelations` trait.
- Removed the deprecated `Contracts\Utils\ConfigurableInterface` as this has not been in use for some time.
- Removed the deprecated `createResourceDocumentValidator()` method from the factory.
- Removed the following previously deprecated methods from the `TestResponse` class:
  - `assertJsonApiResponse()`: use `jsonApi()`.
  - `normalizeIds()` and `normalizeId()` as these are not in use by the refactored test implementation.
- Removed the following previously deprecated methods from the JSON API service/facade:
  - `report()`: no longer supported for access via the service.
  - `requestOrFail()`: no longer required.
- Removed the previously deprecated `Schema\ExtractsAttributesTrait` as it has not been used for some time.

## [1.0.0-beta.6] - 2019-01-03

### Added

- [#277](https://github.com/cloudcreativity/laravel-json-api/pull/277)
  Eloquent adapter can now support soft-deleting, restoring and force-deleting resources by applying a trait to the
  adapter. See the soft-deletes documentation chapter for details.
- [#247](https://github.com/cloudcreativity/laravel-json-api/issues/247)
  New date time rule object to validate a JSON date string is a valid ISO 8601 date and time format.
- [#261](https://github.com/cloudcreativity/laravel-json-api/pull/261)
  Package now supports the JSON API recommendation for asynchronous processing. See the
  [Asynchronous Processing](./docs/features/async.md) chapter for details.
- [#267](https://github.com/cloudcreativity/laravel-json-api/issues/267)
  New adapter hooks are invoked to allow either the filling of additional attributes or dispatching asynchronous
  processes from within an adapter.
- [#246](https://github.com/cloudcreativity/laravel-json-api/issues/246)
  Can now disable providing the existing resource attributes to the resource validator for an update request.
- Added an `existingRelationships` method to the abstract validators class. Child classes can overload this method if
  they need the validator to have access to any existing relationship values for an update request.
- JSON API specification validation will now fail if the `attributes` or `relationships` members have
  `type` or `id` fields.
- JSON API specification validation will now fail if the `attributes` and `relationships` members have common field
  names, as field names share a common namespace.
- Can now return `Responsable` instances from controller hooks.

### Changed

- [#248](https://github.com/cloudcreativity/laravel-json-api/pull/248)
  Adapters now receive the JSON API document (HTTP content) as an array.
- [#278](https://github.com/cloudcreativity/laravel-json-api/pull/278)
  The adapter's `read` method now receives the record being read as its first argument rather than the resource ID. This
  makes it consistent with all other adapter methods that receive the record being read.
- Renamed `Http\Requests\IlluminateRequest` to `Http\Requests\JsonApiRequest`.
- The `getJsonApi()` test helper method now only has two arguments: URI and headers. Previously it accepted data as the
  second argument.
- Improved test assertions and tidied up the test response class. Also added PHP 7 type-hinting to the methods.
- Improve the store aware trait so that it returns a store even if one has not been injected.
- Encoding parameters are now resolved as a singleton in the Laravel container and are bound into the responses factory
  even in custom routes.
- The responses factory `error` method now only creates a response for a single error. For multi-error responses,
  the `errors` method should be used.

### Fixed

- [#201](https://github.com/cloudcreativity/laravel-json-api/issues/201)
  Adapters now receive the array that has been transformed by Laravel's middleware, e.g. trim strings.
- Legacy validators now correctly receive the record when validating a document for an update resource or update
  relationship request. This matches previous behaviour which was removed by accident in `beta.4`.
- [#255](https://github.com/cloudcreativity/laravel-json-api/issues/255)
  Fix invalid pointer for a required resource field when the client does not supply the field.
- [#273](https://github.com/cloudcreativity/laravel-json-api/issues/273)
  Responses class now correctly passes an array of errors to the error repository.
- [#259](https://github.com/cloudcreativity/laravel-json-api/issues/259)
  If a client sends a client-generated ID for a resource that does not support client-generated IDs, a
  `403 Forbidden` response will be sent, as defined in the JSON API spec.

### Removed

- The deprecated `Contracts\Store\AdapterInterface` was removed. Use
  `Contracts\Adapter\ResourceAdapterInterface` instead.
- The deprecated `Adapter\HydratesAttributesTrait` was removed.
- The `Contracts\Http\Requests\RequestInterface` was removed as it is no longer necessary (because the package is no
  longer framework-agnostic).
- Removed the `Contracts\Repository\SchemasRepositoryInterface` and `Repository\SchemasRepository` class because these
  were not in use.
- The previously deprecated `InteractsWithModels` testing trait was removed.
- The following (majority previously deprecated methods) on the `TestResponse` class were removed:
  - `assertDocument`
  - `assertResourceResponse`
  - `assertResourcesResponse`
  - `assertRelatedResourcesResponse`
  - `assertSearchResponse`
  - `assertSearchOneResponse`
  - `assertCreateResponse`
  - `assertReadResponse`
  - `assertUpdateResponse`
  - `assertDeleteResponse`
  - `assertRelatedResourceResponse`
  - `assertHasOneRelationshipResponse`
  - `assertDataCollection`
  - `assertDataResource`
  - `assertDataResourceIdentifier`
  - `assertSearchByIdResponse`
  - `assertSearchedPolymorphIds`
  - `assertReadPolymorphHasMany`

### Deprecated

- All interfaces in the `Contracts\Object` namespace will be removed for `2.0`.
- All classes in the `Object` namespace will be removed for `2.0`.
- A number of methods on the `MakesJsonApiRequests` and `TestResponse` classes have been deprecated as they are better
  named equivalents or they are not applicable to the current testing approach. These will be removed in `2.0`.

## [1.0.0-beta.5] - 2018-10-13

### Fixed

- [#240](https://github.com/cloudcreativity/laravel-json-api/issues/240)
  Ensure PHP class name is passed to authorizer when authorizing fetch-many and create resource requests.

## [1.0.0-beta.4] - 2018-10-11

### Added

- New validation implementation that uses Laravel validators throughout. See the
  [validation documentation](./docs/basics/validators.md) for details.
- Errors for the new validation implementation now have their `title`, `detail` and `code` members translated via
  Laravel's translator. This means consuming applications can change the messages by overriding the default translations
  provided by this package.
- [#234](https://github.com/cloudcreativity/laravel-json-api/issues/234)
  HTTP exception headers are now added to the JSON API response when converting to JSON API errors.

### Changed

- Minimum PHP version is now `7.1`.
- Minimum Laravel version is now `5.5`.
- The validated request object is now abstract, with a concrete implementation for each type of request.
- [#237](https://github.com/cloudcreativity/laravel-json-api/issues/237)
  The route key name is now used as the default cursor key name.

### Removed

- The `Auth\HandlesAuthorizers` trait was removed and its logic moved into the `Authorize` middleware.

### Deprecated

- The previous validation solution is deprecated in favour of the new solution and will be removed at `2.0`.
- The error factory implementation is deprecated in favour of using Laravel translation and will be removed at `2.0`.
- The `Document\Error` and `Contracts\Document\MutableErrorInterface` are deprecated and will be removed at `2.0`. You
  should use the error interface/class from the `neomerx/jsonapi` package instead.
- The following utility classes/traits/interfaces are deprecated and will be removed at `2.0`:
  - `Utils/ErrorCreatorTrait`
  - `Utils/ErrorsAwareTrait` and `Contracts\Utils\ErrorsAwareInterface`
  - `Utils/Pointers`
  - `Utils/Replacer` and `Contracts\Utils\ReplacerInterface`
- The `Contracts\Factories\FactoryInterface` is deprecated and will be removed at `1.0`. You should
  type-hint `Factories\Factory` directly instead.

## [1.0.0-beta.3] - 2018-09-21

### Added

- New cursor-based paging strategy, refer to the [Pagination docs](./docs/fetching/pagination.md) for details.
- Refactored the client interface and implementation, improving record serializing and adding missing relationship
  request methods.
- [#123](https://github.com/cloudcreativity/laravel-json-api/issues/123)
  Can now use a custom resolver if wanting to override the default namespace resolution provided by this package. This
  is documented in the [Resolvers chapter.](./docs/features/resolvers.md)

### Changed

- Extract model sorting from the Eloquent adapter into a `SortsModels` trait.
- [#144](https://github.com/cloudcreativity/laravel-json-api/issues/144)
  Improved the helper method that creates new client instances so that it automatically adds a base URI for the client
  if one is not provided.

### Fixed

- [#222](https://github.com/cloudcreativity/laravel-json-api/issues/222)
  Adding related resources to a has-many relationship now does not add duplicates. The JSON API spec states that
  duplicates must not be added, but the default Laravel behaviour does add duplicates. The `HasMany` relationship now
  takes care of this by filtering out duplicates before adding them.

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

- JSON API document is now only parsed out of the request if data is expected within the document. This ensures that
  Laravel's get JSON test helpers can be used.
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
- Exception messages are no longer pushed into the JSON API error detail member, unless the Exception is a HTTP
  Exception.
- [#219](https://github.com/cloudcreativity/laravel-json-api/issues/219)
  Can now use the `id` filter as one of many filters, previously the `id` filter ignored any other filters provided. It
  now also respects sort and paging parameters.

## [1.0.0-alpha.4] - 2018-07-02

### Added

- [#203](https://github.com/cloudcreativity/laravel-json-api/issues/203)
  JSON API container now checks whether there is a Laravel container binding for a class name. This allows schemas,
  adapters etc to be bound into the container rather than having to exist as actual classes.

### Fixed

- [#202](https://github.com/cloudcreativity/laravel-json-api/issues/202)
  When appending the schema and host on a request, the base URL is now also appended. This caters for Laravel
  applications that are served from host sub-directories.

## [1.0.0-alpha.3] - 2018-05-17

### Added

- Errors that occur *before* a route is processed by a JSON API are now sent to the client as JSON API error responses
  if the client wants a JSON API response. This is determined using the `Accept` header and means that exceptions such
  as the maintenance mode exception are correctly returned as JSON API errors if that is what the client wants.
- Can now override the default API name via the JSON API facade.

### Changed

- Field guarding that was previously available on the Eloquent adapter is now also available on the generic adapter.
- Extracted the logic for an Eloquent `hasManyThrough` relation into its own relationship adapter (was previously in
  the `has-many` adapter).
- Moved the `FindsManyResources` trait from the `Store` namespace to `Adapter\Concerns`.
- The `hydrateRelationships` method on the `AbstractResourceAdapter` is no longer abstract as it now contains the
  implementation that was previously on the Eloquent adapter.
- The test exception handler has been moved from the dummy app to the `Testing` namespace. This means it can now be used
  when testing JSON API packages.
- Merged the two resolvers provided by this package into a single class.
- [#176](https://github.com/cloudcreativity/laravel-json-api/issues/176)
  When using *not-by-resource* resolution, the type of the class is now appended to the class name. E.g.
  `App\JsonApi\Adapters\PostAdapter` is now expected instead of `App\JsonApi\Adapters\Post`. The previous behaviour can
  be maintained by setting the `by-resource` config option to the string `false-0.x`.
- The constructor dependencies for the `Repositories\ErrorRepository` have been simplified.

### Fixed

- Resolver was not correctly classifying the resource type when resolution was not by resource.
- [#176](https://github.com/cloudcreativity/laravel-json-api/issues/176)
  Do not import model class in Eloquent adapter stub to avoid collisions with class name when using the legacy
  *not-by-resource* behaviour.
- An exception is no longer triggered when create JSON API responses when there is no booted JSON API handling the
  request.
- [#181](https://github.com/cloudcreativity/laravel-json-api/issues/181) Send a `419` error response with an error
  object for a `TokenMismatchException`.
- [#182](https://github.com/cloudcreativity/laravel-json-api/issues/182) Send a `422` error response with JSON API error
  objects when a `ValidationException` is thrown outside of JSON API validation.

### Deprecated

- The `report` method on the JSON API service/facade will be removed by `1.0.0`.

## [1.0.0-alpha.2] - 2018-05-06

### Added

- New authorizer interface and an abstract class that better integrates with Laravel's authentication and authorization
  style. See the new [Security chapter](./docs/basics/security.md) for details.
- Can now generate authorizers using the `make:json-api:authorizer` command, or the `--auth` flag when generating a
  resource with `make:json-api:resource`.
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

- Eloquent schemas are now deprecated in favour of using generic schemas. This is because of the amount of processing
  involved without any benefit, as generic schemas are straight-forward to construct. The following classes/traits are
  deprecated:
  - `Eloquent\AbstractSchema`
  - `Eloquent\SerializesModels`
  - `Schema\CreatesLinks`
  - `Schema\EloquentSchema` (was deprecated in `1.0.0-alpha.1`).

## [1.0.0-alpha.1] - 2018-04-29

As we are now only developing JSON API within Laravel applications, we have deprecated our framework agnostic
`cloudcreativity/json-api` package. All the classes from that package have been merged into this package and renamed to
the `CloudCreativity\LaravelJsonApi` namespace. This will allow us to more rapidly develop this Laravel package and
simplify the code in subsequent releases.

### Added

- New Eloquent relationship adapters allows full support for relationship endpoints.
- Message bags can now have their keys mapped and/or dasherized when converting them to JSON API errors in
  the `ErrorBag` class.
- JSON API resource paths are now automatically converted to model relationship paths for eager loading in the Eloquent
  adapter.
- The Eloquent adapter now applies eager loading when reading or updating a specific resource.
- Eloquent adapters can now *guard* JSON API fields via their `$guarded` and `$fillable` properties. These are used when
  filling attributes and relationships.
- Added standard serialization of relationships within Eloquent schemas. This always serializes `self` and
  `related` links for listed model relationships, and only adds the relationship `data` if the relationship is being
  included in a compound document.

### Changed

- By default resources no longer need to have a controller as the generic JSON API controller will now handle any
  resource. If resources have controllers, the `controller` routing option can be set to a string controller name,
  or `true` to use a controller with the same name as the resource.
- Split adapter into resource and relationship adapter, and created classes to specifically deal with Eloquent
  relationships.
- Adapters now handle both reading and modifying domain records.
- Moved Eloquent JSON API classes into a single namespace.
- Moved logic from Eloquent controller into the JSON API controller as the logic is no longer specific to handling
  resources that related to Eloquent models.
- Filter, sort and page query parameters are no longer allowed for requests on primary resources (create, read update
  and delete) because these query parameters do not apply to these requests.
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
  Filter, sort and page parameters validation rules are excluded for resource requests for which those parameters do not
  apply (create, read, update and delete).
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
