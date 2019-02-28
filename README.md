[![Build Status](https://travis-ci.org/cloudcreativity/laravel-json-api.svg?branch=master)](https://travis-ci.org/cloudcreativity/laravel-json-api)

# cloudcreativity/laravel-json-api

Build feature-rich and standards-compliant APIs in Laravel. 

This package provides all the capabilities you need to add [JSON API](http://jsonapi.org)
compliant APIs to your application. Extensive support for the specification, including:

- Fetching resources
- Fetching relationships
- Inclusion of related resources (compound documents)
- Sparse fieldsets.
- Sorting.
- Pagination.
- Filtering
- Creating resources.
- Updating resources.
- Updating relationships.
- Deleting resources.
- Validation of:
  - JSON API documents; and
  - Query parameters.
 
The following additional features are also supported:

- Full support for Eloquent resources, with features such as:
  - Automatic eager loading when including related resources.
  - Easy relationship end-points.
  - Soft-deleting and restoring Eloquent resources.
  - Page and cursor based pagination.
- Asynchronous processing.
- Support multiple media-types within your API.
- Generators for all the classes you need to add a resource to your API.

Installation instructions and documentation are available on
[Read the Docs](http://laravel-json-api.readthedocs.io/en/latest/).

> Credit where credit is due: this package is based on the framework agnostic package
[neomerx/json-api](https://github.com/neomerx/json-api).

## What is JSON API?

From [jsonapi.org](http://jsonapi.org)

> If you've ever argued with your team about the way your JSON responses should be formatted, JSON API is your 
anti-bikeshedding weapon.
>
> By following shared conventions, you can increase productivity, take advantage of generalized tooling, and focus 
on what matters: your application. Clients built around JSON API are able to take advantage of its features around 
efficiently caching responses, sometimes eliminating network requests entirely.

For full information on the spec, plus examples, see [http://jsonapi.org](http://jsonapi.org).

## Demo

A demo application is available at [here](https://github.com/cloudcreativity/demo-laravel-json-api).

## Laravel Versions

| Laravel | This Package | EOL? |
| --- | --- | --- |
| 5.8.* | `^1.0` | - |
| 5.7.* | `^1.0` | - |
| 5.6.* | `^1.0` | - |
| 5.5.* | `^1.0` | - |
| 5.4.* | `1.0.0-beta.3` | EOL |

Make sure you consult the [Upgrade Guide](http://laravel-json-api.readthedocs.io/en/latest/upgrade/) 
when upgrading.

## Lumen

Currently we have not integrated the package with Lumen. We do not have any active projects that use Lumen,
so if you do and can help, please let us know on
[this issue](https://github.com/cloudcreativity/laravel-json-api/issues/61).

## Documentation

Documentation is available on [Read the Docs](http://laravel-json-api.readthedocs.io/en/latest/).

## License

Apache License (Version 2.0). Please see [License File](LICENSE) for more information.

## Installation

Installation is via `composer`. See the documentation for complete instructions.

## Contributing

Contributions are absolutely welcome. Ideally submit a pull request, even more ideally with unit tests. 
Please note the following:

- **Bug Fixes** - submit a pull request against the `master` branch.
- **Enhancements / New Features** - submit a pull request against the `develop` branch.

We recommend submitting an issue before taking the time to put together a pull request.
