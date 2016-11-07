# Change Log
All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## [Unreleased 0.5.1]

### Changed
- Dependency `symfony/psr-http-message-bridge` now allows `^1.0` as version 1 has now been released. Version `0.2.*` is
still allowed to maintain backwards compatibility but will be removed in `v0.6` of this package.

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
