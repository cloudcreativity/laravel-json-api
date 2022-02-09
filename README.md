![Tests](https://github.com/cloudcreativity/laravel-json-api/workflows/Tests/badge.svg)

# cloudcreativity/laravel-json-api

## Status

**DO NOT USE THIS PACKAGE FOR NEW PROJECTS - USE [laravel-json-api/laravel](https://github.com/laravel-json-api/laravel)
INSTEAD.**

This package has now been rewritten, substantially improved and released as the `laravel-json-api/laravel` package.
Documentation for the new version is available on our new website [laraveljsonapi.io](https://laraveljsonapi.io) and the
code is now developed under the
[Laravel JSON:API Github organisation.](https://github.com/laravel-json-api)

The `cloudcreativity/laravel-json-api` package is now considered to be the *legacy* package. As we know it is in use in
a lot of production applications, it will continue to receive bug fixes and updates for new Laravel versions. However,
it is no longer supported for new features.

If you are starting a new project, you MUST use the
[new package `laravel-json-api/laravel`.](https://github.com/laravel-json-api/laravel)

## Introduction

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

### What is JSON API?

From [jsonapi.org](http://jsonapi.org)

> If you've ever argued with your team about the way your JSON responses should be formatted, JSON API is your
> anti-bikeshedding weapon.
>
> By following shared conventions, you can increase productivity, take advantage of generalized tooling, and focus on
> what matters: your application. Clients built around JSON API are able to take advantage of its features around
> efficiently caching responses, sometimes eliminating network requests entirely.

For full information on the spec, plus examples, see [http://jsonapi.org](http://jsonapi.org).

## Documentation

Full package documentation is available on
[Read the Docs](http://laravel-json-api.readthedocs.io/en/latest/).

## Slack

Join the Laravel JSON:API community on
[Slack.](https://join.slack.com/t/laraveljsonapi/shared_invite/zt-e3oi2r4y-8nkmhzpKnPQViaXrkPJHtQ)

## Laravel Versions

| Laravel | This Package |
| --- | --- |
| `^9.0` | `^4.0` |
| `^8.0` | `^3.0|^4.0` |
| `^7.0` | `^2.0` |
| `^6.0` | `^1.7` |
| `5.8.*` | `^1.7` |
| `5.7.*` | `^1.0` |
| `5.6.*` | `^1.0` |
| `5.5.*` | `^1.0` |

Make sure you consult the [Upgrade Guide](http://laravel-json-api.readthedocs.io/en/latest/upgrade/)
when upgrading between major or pre-release versions.

## License

Apache License (Version 2.0). Please see [License File](LICENSE) for more information.

## Installation

Installation is via `composer`. See the documentation for complete instructions.

## Contributing

Contributions are absolutely welcome. Ideally submit a pull request, even more ideally with unit tests. Please note the
following:

- **Bug Fixes** - submit a pull request against the `master` branch.
- **Enhancements / New Features** - submit a pull request against the `develop` branch.

We recommend submitting an issue before taking the time to put together a pull request.
