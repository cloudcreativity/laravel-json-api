[![Build Status](https://travis-ci.org/cloudcreativity/laravel-json-api.svg?branch=master)](https://travis-ci.org/cloudcreativity/laravel-json-api)

# cloudcreativity/laravel-json-api

Add [jsonapi.org](http://jsonapi.org) compliant APIs to your Laravel 5 application. 
Based on the framework agnostic package [neomerx/json-api](https://github.com/neomerx/json-api).

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

| Laravel | This Package |
| --- | --- |
| 5.7.* | `1.0.0-beta.1` |
| 5.6.* | `1.0.0-beta.1` |
| 5.5.* | `1.0.0-beta.1` |
| 5.4.* | `1.0.0-beta.1` |

Make sure you consult the [Upgrade Guide](http://laravel-json-api.readthedocs.io/en/latest/upgrade/) when upgrading.

> We plan to release `1.0.0` with a minimum PHP version of `7.1`. This means we will drop support for Laravel 5.4
at some point during the `beta` release cycle.

## Lumen

Currently we have not integrated the package with Lumen. We do not have any active projects that use Lumen,
so if you do and can help, please let us know on
[this issue](https://github.com/cloudcreativity/laravel-json-api/issues/61).

## Documentation

Documentation is available on [Read the Docs](http://laravel-json-api.readthedocs.io/en/latest/).

## Status

We are aiming for v1.0 as soon as possible and no we are on `1.0.0-beta.*` releases there will be a minimal
amount of breaking changes, that will be limited to fixing the remaining issues.
[Check the progress here.](https://github.com/cloudcreativity/laravel-json-api/milestone/2)

We have production applications that are using the package and extensive test coverage of this package and
those applications.

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
