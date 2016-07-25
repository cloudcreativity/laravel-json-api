# Change Log
All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## [Unreleased]

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
