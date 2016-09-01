# Change Log
All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

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
