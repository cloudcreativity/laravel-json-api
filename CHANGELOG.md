# Change Log
All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

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
- Resources no longer need to have a controller as the generic JSON API controller will now handle any resource.
Any resource that does not have a controller must use `false` as its `controller` option when registering the 
resource routes.

### Changed
- Split adapter into resource and relationship adapter, and created classes to specifically deal with Eloquent
relationships.
- Adapters now handle both reading and modifying domain records.
- Moved Eloquent JSON API classes into a single namespace.
- Moved logic from Eloquent controller into the JSON API controller as the logic is no longer specific to
handling resources that related to Eloquent models.
- Filter, sort and page query parameters are no longer allowed for requests on primary resources (create, read
update and delete) because these query parameters do not apply to these requests.

### Removed
- Delete Eloquent hydrator class as all hydration is now handled by adapters instead.
- The utility `Fqn` class has been removed as namespace resolution is now done by resolvers.
- The deprecated `Str` utility class has been removed. Use `CloudCreativity\JsonApi\Utils\Str` instead.

### Deprecated
- The Eloquent controller is deprecated in favour using the JSON API controller directly.
- The `Schema\EloquentSchema` is deprecated in favour of using the `Eloquent\AbstractSchema`.
- The `Store\EloquentAdapter` is deprecated in favour of using the `Eloquent\AbstractAdapter`.

### Fixed
- [#128](https://github.com/cloudcreativity/laravel-json-api/issues/128) 
Filter, sort and page parameters validation rules are excluded for resource requests for which those
parameters do not apply (create, read, update and delete).
- [#92](https://github.com/cloudcreativity/laravel-json-api/issues/92)
Last page link is now excluded if there are pages, rather than linking to page zero.
- [#67](https://github.com/cloudcreativity/laravel-json-api/issues/67)
Pagination meta will no longer leak into error response if error occurs when encoding data.

## [0.12.0] - 2018-02-08

### Added
- Package now supports Laravel 5.6 in addition to Laravel 5.4 and 5.5.

## [0.11.5] - 2018-02-06

### Fixed
- [#142](https://github.com/cloudcreativity/laravel-json-api/issues/142)
URLs were incorrectly formed when there is no URL namespace.

## [0.11.4] - 2018-01-25

### Fixed
- [#138](https://github.com/cloudcreativity/laravel-json-api/issues/138)
Generator commands not working since Laravel v5.5.28.
- [#131](https://github.com/cloudcreativity/laravel-json-api/issues/131)
Ensure Eloquent adapter uses default pagination parameters.

## [0.11.3] - 2017-12-01

### Fixed
- [#125](https://github.com/cloudcreativity/laravel-json-api/issues/125)
Refresh the Eloquent model after hydrating so that any cached relationships are reloaded.

## [0.11.2] - 2017-11-14

### Added
- The host can now be omitted from encoded URLs if needed.

### Changed
- The test response `assertStatus` method now outputs the error messages if the response status is
unexpected and the response has JSON API errors in it. This restores a the behaviour that was present
in `v0.10`.

### Fixed
- Corrected invalid import statement in abstract hydrator stub.

## [0.11.1] - 2017-09-26

### Fixed
- [#109] The matched media type is now set as the `Content-Type` header on a response.

## [0.11.0] - 2017-09-02

### Added
- Support for Laravel 5.5, including package discovery.
- Client supplied ids will now be hydrated into Eloquent models, configurable via the `$clientId` property
on the Eloquent hydrator.

### Removed
- The following deprecated methods were removed from the `TestResponse` helper:
  - `seeStatusCode()`: use `assertStatus()`
  - `seeDataCollection()`: use `assertDataCollection()`
  - `seeDataResource()`: use `assertDataResource()`
  - `seeDataResourceIdentifier()`: use `assertDataResourceIdentifier()`
  - `seeDocument()`: use `assertDocument()`
  - `seeErrors()`: use `assertErrors()` 

### Deprecated
- The `TestResponse::assertStatusCode()` method is deprecated in favour of `assertStatus()`.
- The `InteractsWithModels::seeModelInDatabase()` method is deprecated in favour of `assertDatabaseHasModel()`.
- The `InteractsWithModels::notSeeModelInDatabase()` method is deprecated in favour of `assertDatabaseMissingModel()`.

## [0.10.3] - 2017-09-02

### Fixed
-[#96] Fixed creation of qualified sorting parameters in the Eloquent adapter. 

## [0.10.2] - 2017-08-25

### Added
- Test assertions to check that resource routes have not been registered.

## [0.10.1] - 2017-08-15

### Fixed
- [#88] Fixed fatal error caused when resolving request objects out of the service container when there was no 
inbound request bound in the service container.

## [0.10.0] - 2017-07-29

### Added
- The resource registrar now automatically adds a JSON API's route URL and name prefixes.
- Can now send JSON API requests using a Guzzle client.
- Can now obtain a JSON API instance using the `json_api()` global helper.
- Can now obtain the JSON API request instance using the `json_api_request()` global helper.
- Added an api `url()` helper for generating URLs to resources within an API.
- Can now map a single JSON API resource to multiple record classes. 

### Changed
- Tests helpers are no longer in the Browser Kit testing style, and instead use a `TestResponse` class that extends
the standard Laravel test response.
- The `InteractsWithResources` test helper trait has been merged into `MakesJsonApiRequests`.
- The `ReplyTrait` has been moved to the `Http\Controllers` namespace and renamed `CreatesResponses`.
- Moved the facade into the `Facades` namespace and renamed it `JsonApi`. This means it can now be imported with
a `use` statement.
- The `register()` method must now be used to register routes.

### Fixed
- [#66] Content negotiation no longer sends a `415` response if a request does not have body content.
- Fixed merging API resources objects.

### Removed
- This package no longer supports Laravel 5.3.
- The `Document\GeneratesLink` trait was removed.
- The `Document\LinkFactory` was removed and the API `links()` helper must be used instead.

## [0.9.1] - 2017-06-26

### Fixed
- [#70] Url prefix was not being passed to encoder leading to invalid links in JSON API documents.

## [0.9.0] - 2017-06-07

### Added
- An encoder can now be obtained for a named API via the JSON API service `encoder` method.
- New Blade directives for outputting encoded JSON API data in templates:
  - `@jsonapi($apiName)` for configuring the encoder to use. Optionally takes `$host`, `$options` and `$depth`
  arguments.
  - `@encode($data)` for encoding data. Optionally takes `$includePaths` and `$fieldSets` arguments.
- Added a broadcasting helper trait (`Broadcasting\BroadcastsData`) to aid broadcasting of JSON API data.

### Changed
- Changes to import statements for resource object and standard object interfaces, to reflect changes in the 
`cloudcreativity/json-api` package.

### Removed
- Removed the validator error factory interface from this library as the one provided by `cloudcreativity/json-api`
has the additional methods on it.
- The deprecated `isActive` method on the JSON API service (available via the facade) has been removed.

## [0.8.0] - 2017-05-20

### Added
- Generator for API config - `php artisan make:json-api <name>`
- All routes registered now have route names.
- Support for packages providing resources into an API via a `ResourceProvider` class. Note that this is currently
experimental.

### Changed
- Route registration has been refactored so that only the JSON API routes that are required for a specific resource
type are registered.
- Config is now defined on a per-API basis. E.g. for an API named `v1`, config is stored in a `json-api-v1.php` file.
- Store adapters now relate to a specific resource type, and also contain all filtering/pagination logic for an
index HTTP request.
- The validators class now provides query parameter validation rules rather than rules specifically for the filter
query parameter.
- Pagination is now implemented via a pagination strategy that is injected into the `EloquentAdapter`. This allows
for pagination strategies to be changed on a per-resource type basis. The package includes a `StandardStrategy` that
integrates with the default Laravel page number/size pagination.

### Removed
- A resource's `Request` class has been removed as its functionality is now handled by middleware, and query parameter
checking has been moved to the `Validators` class.
- `Search` classes have been removed in favour of the new store adapters.
- Package no longer supports Laravel 5.1 and 5.2.

### Fixed
- Shortcut for non-Eloquent generation on generator commands has been changed to `-N` (was previously `-ne` which
did not work).

## [0.7.0] - 2017-03-16

### Added
- Default Eloquent hydration attributes are now calculated using `Model::getFillable()`. To use this,
set the `$attributes` property on an Eloquent hydrator to `null`.
- Eloquent hydrator will now calculate attributes to serialize as dates using `Model::getDates()`. To
use this, set the `$dates` property on an Eloquent hydrator to `null`.
- Default Eloquent serialization attribution in the `EloquentSchema` class. These are calculated using
either `Model::getVisible()` or `Model::getFillable()` minus any `Model::getHidden()` keys. To use this,
set the `$attributes` property on an Eloquent schema to `null`.

### Changed
- When generating an Eloquent hydrator or schema using the `make:json-api` commands, the `$attributes`
property will be set as `null` by default.

## [0.6.2] - 2017-02-23

### Fixed
- Corrected mistake in description of the `make:json-api:validators` Artisan command.

## [0.6.1] - 2017-02-22

### Changed
- Updated service provider bindings for the `neomerx/json-api` factories.

## [0.6.0] - 2017-02-20

### Added
- Added support for Laravel 5.4. However, consuming applications will need to use the Browserkit testing package
to continue to use the JSON API testing suite.

### Removed
- Dropped support for PHP 5.5

## [0.5.4] - 2016-12-21

### Fixed
- Bug in Eloquent adapter meant that a check for whether a resource identifier exists was checking whether the
whole table had any rows, rather than the specific id.

## [0.5.3] - 2016-12-01

### Fixed
- #31 Fix expression in abstract generator command that is not compatible with PHP 5.5

## [0.5.2] - 2016-11-11

### Added
- Generator commands to generate the classes required for a JSON API resource. To view available commands use 
`php artisan list make:json-api`. The main command is `make:json-api:resource` which generates multiple classes for
a JSON API resource at once. Credit to @jstoone for contributing this feature.

## [0.5.1] - 2016-11-09

### Changed
- Dependency `symfony/psr-http-message-bridge` now allows `^1.0` as version 1 has now been released. Version `0.2.*` is
still allowed to maintain backwards compatibility but will be removed in `v0.6` of this package.

### Fixed
- Amended testing that a model has been trashed so that it is compatible with Laravel 5.2 and 5.3.

## [0.5.0] - 2016-10-21

This release is the first release to support Laravel 5.3.

### Added
- Can now attach custom adapters to the store via the `json-api.php` config file.
- Authorizer now supports adding error messages from the error repository using string keys.
- An abstract Eloquent model hydrator can now be used.
- The Eloquent controller now handles saving of has many relationships that need to be committed to the database
after the primary model has been created. You must be using the new Eloquent hydrator for this to work, or your 
existing hydrator can type hint the capability by implementing the new `HydratesRelatedInterface`.

### Changed
- Paging configuration is now held on a per-API basis.
- Update authorization can now access the resource submitted by the client via an additional method argument.
- Request handlers are now not injected to a controller via the constructor: instead the fully qualified class name
is passed and controller middleware is used to validate requests. This change was necessary to support Laravel 5.3, 
while maintaining support for 5.1 and 5.2.
- Various classes have been moved into the `cloudcreativity/json-api` package (changing their namespace), because the
implementations are now framework agnostic.
- Eloquent schemas now follow the JSON API recommendation and use hyphenated member names by default. This behaviour
can however be overridden, e.g. if the Eloquent underscored attribute names is the desired convention.
- To match this, the search class also assumed a default of hyphenated member names, although this can be overridden.
- Validator provides now receive the resource type that they are validating into their method signatures. This allows
for a single validator provider to validate multiple resource types if desired.

### Removed
- `AbstractSortedSearch` was removed and its capability merged into `AbstractSearch` as there was no need to have the
two separate classes.
- Removed the experimental `boot` feature on the JSON API service.

## [0.4.6] - 2016-09-01

### Fixed
- Return value from hydrator was not being used in the Eloquent controller. The hydrator interface defines that the
hydrated object should be returned, so the controller has been amended to respect this return value.

## [0.4.5] - 2016-08-26

### Change
- Tied the package to Laravel `5.1.*|5.2.*`. This is non-breaking because the package currently only works with these
two versions (it was previously tied to `^5.1`). A breaking change in Laravel `5.3` means that this package does not
currently work with `5.3`.

## [0.4.4] - 2016-08-26

### Fixed
- Paginator now includes sort order in paging links. This is required because otherwise the paging does not maintain
its order when using the links.

## [0.4.3] - 2016-08-16

### Added

- `AbstractSortedSearch` now has a `defaultSort` method that is invoked if the client has not sent any sort
parameters. Child classes can override this method to implement a default search order if desired.
- Added `saving`, `saved`, `deleting` and `deleted` hooks to the `EloquentController`.

## [0.4.2] - 2016-08-11

### Added

- Can now register a resource type with the resource registrar without providing a controller name. The controller
will default to the studley case of the resource type with `Controller` appended - e.g. `posts` becomes 
`PostsController`.
- New `InteractsWithResources` test helper. This extends `MakesJsonApiRequests` and adds in helpers for using
when a test case is testing a single resource type.

## [0.4.1] - 2016-07-27

### Added

- Support for find-many requests in the default Eloquent model search implementation.
- Hooks in `EloquentController` for `creating`, `created`, `updating` and `updated` events. This makes it
easier for child classes to implement customer logic, e.g. dispatching events, jobs, etc.

### Changed

- Removed dependency between model test helper and Laravel's database test helper, as there was no need for
this dependency to exist.

### Fixed

- Asserting that a model has been created now correctly checks expected attributes.

## [0.4.0] - 2016-07-20

This is a substantial refactoring, based on using this package in production environments. We also updated 
the underlying packages, both of which are breaking changes:

- `neomerx/json-api` from `^0.6.6` to `^0.8.0`
- `cloudcreativity/json-api` from `^0.4.0` to `^0.5.0`

Future changelog entries will document changes from this point onwards.
