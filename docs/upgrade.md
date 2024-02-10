# Upgrade Guide

## 4.x to 5.0

### Background

This major release upgrades the underlying `neomerx/json-api` dependency from `v1` to `v5` of our fork,
`laravel-json-api/neomerx-json-api`.

Upgrading this dependency means that both this package (`cloudcreativity/laravel-json-api`) and the newer package
(`laravel-json-api/laravel`) now use the same version of the Neomerx encoder. This means applications can now install
both this package and the newer package, unlocking an upgrade path between the two. While you cannot have an API that
mixes the two packages, an application could now have an older API running off the old package, and a newer API
implemented with the new package. Overtime you can deprecate the older API and eventually remove it - removing
`cloudcreativity/laravel-json-api` in the process.

In case you're not aware, the Neomerx dependency is the package that does the encoding of classes to the JSON:API
formatting. The problem we have always had with `cloudcreativity/laravel-json-api` is the package is too tightly
coupled to the Neomerx implementation. This means this upgrade is a major internal change. While we have tested the
upgrade to the best of our ability, if you find problems with it then please report them as issues on Github.

While the new package (`laravel-json-api/laravel`) does use the Neomerx encoder, the use of that encoder is hidden
behind generic interfaces. This fixed the problems with coupling and was one of the main motivations in building the
new package.

### Upgrading

To upgrade, run the following Composer command:

```bash
composer require cloudcreativity/laravel-json-api:5.0.0-alpha.1
```

We've documented the changes that most applications will need to make below. However, if your application has made any
changes to the implementation, e.g. by overriding elements of our implementation or using any of the Neomerx classes
directly, there may be additional changes to make. If you're unsure how to upgrade anything, create a Github issue. 

### Import and Type-Hint Renaming

Most of the upgrade can be completed by doing a search and replace for import statements and type-hints.

Your application will definitely be using the following import statements that must be replaced:

- `Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface` replace with
  `CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersInterface`
- `Neomerx\JsonApi\Encoder\Parameters\EncodingParameters` replace with
  `CloudCreativity\LaravelJsonApi\Http\Query\QueryParameters`
- `Neomerx\JsonApi\Schema\SchemaProvider` replace with
  `CloudCreativity\LaravelJsonApi\Schema\SchemaProvider`

And it will also definitely be using these type-hints, that must be replaced:

- `EncodingParametersInterface` with `QueryParametersInterface`
- `EncodingParameters` with `QueryParameters`

The following import statements also need changing, however you should not worry if you cannot find any usages of them
within your application:

- `Neomerx\JsonApi\Contracts\Encoder\Parameters\SortParameterInterface` replace with
  `CloudCreativity\LaravelJsonApi\Contracts\Http\Query\SortParameterInterface`
- `Neomerx\JsonApi\Encoder\Parameters\SortParameter` replace with
  `CloudCreativity\LaravelJsonApi\Http\Query\SortParameter`
- `Neomerx\JsonApi\Contracts\Document\ErrorInterface` replace with
  `Neomerx\JsonApi\Contracts\Schema\ErrorInterface`
- `Neomerx\JsonApi\Document\Error` replace with
  `Neomerx\JsonApi\Schema\Error`
- `Neomerx\JsonApi\Exceptions\ErrorCollection` replace with
  `Neomerx\JsonApi\Schema\ErrorCollection`
- `Neomerx\JsonApi\Contracts\Document\LinkInterface` replace with
  `Neomerx\JsonApi\Contracts\Schema\LinkInterface`
- `Neomerx\JsonApi\Contracts\Document\DocumentInterface` replace with
  `Neomerx\JsonApi\Contracts\Schema\DocumentInterface`
- `Neomerx\JsonApi\Contracts\Http\Headers\HeaderInterface` replace with
  `CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderInterface`
- `Neomerx\JsonApi\Contracts\Http\Headers\AcceptHeaderInterface` replace with
  `CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\AcceptHeaderInterface`
- `Neomerx\JsonApi\Contracts\Http\Headers\HeaderParametersParserInterface` replace with
  `CloudCreativity\LaravelJsonApi\Contracts\Http\Headers\HeaderParametersParserInterface`
- `Neomerx\JsonApi\Contracts\Http\Query\QueryParametersParserInterface` replace with
  `CloudCreativity\LaravelJsonApi\Contracts\Http\Query\QueryParametersParserInterface`

### Schemas

We have added argument and return type-hints to all methods on the schema class. You will need to add these to all your
schemas. For example the `getId()`, `getAttributes()` and `getRelationships()` methods now look like this:

```php
public function getId(object $resource): string {}

public function getAttributes(object $resource): array {}

public function getRelationships(object $resource, bool $isPrimary, array $includeRelationships): array {}
```

In addition, properties also now have type-hints. For example, you need to add a `string` type-hint to the
`$resourceType` property.

Optionally, you can remove the `getId()` method from model schemas if the content of the method looks like this:

```php
public function getId(object $resource): string
{
    return (string) $resource->getRouteKey();
}
```
The functions that are used to call meta data has also been changed. Before there were these 2 functions:

```php
public function getPrimaryMeta($resource)
{
    return ['foo => 'bar'];
}
public function getInclusionMeta($resource)
{
    return $this->getPrimaryMeta($resource);
}
```

These have now been replaced with 1 function:

```php
  public function getResourceMeta($resource): ?array
  {
      return ['foo => 'bar'];
  }
```
This method will be used in place of the other 2. In the rare event that your inclution meta was different from primary, you may need to amalgemate.

### Errors

Check whether you are using the Neomerx error object directly anywhere, by searching for the new import statement:
`Neomerx\JsonApi\Schema\Error`. If you are, you should be aware that the constructor arguments have changed. Check
your use against the new constructor arguments by inspecting the class directly.

## 3.x to 4.0

[Use this link to view the 4.0 upgrade guide.](https://github.com/cloudcreativity/laravel-json-api/blob/v4.0.0/docs/upgrade.md)

## 2.x to 3.0

[Use this link to view the 3.0 upgrade guide.](https://github.com/cloudcreativity/laravel-json-api/blob/v3.3.0/docs/upgrade.md)
