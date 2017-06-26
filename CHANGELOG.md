# Change Log
All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

### Fixed
- [#66] Content negotiation no longer sends a `415` response if a request does not have body content.

### Removed
- This package no longer supports Laravel 5.3

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
