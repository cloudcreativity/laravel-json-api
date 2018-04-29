# Change Log
All notable changes to this project will be documented in this file. This project adheres to
[Semantic Versioning](http://semver.org/) and [this changelog format](http://keepachangelog.com/).

## Unreleased

### Added
- The Eloquent controller now has the following additional hooks:
  - `searching` for an *index* action.
  - `reading` for a *read* action.

### Changed
- Generating an Eloquent schema will now generate a class that extends `SchemaProvider`, i.e. the generic schema.
- Existing Eloquent controller hooks now receive the whole validate JSON API request rather than just the resource
object submitted by the client.

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
