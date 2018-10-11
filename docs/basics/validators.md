# Validation

## Introduction

This package automatically checks both request query parameters and content for compliance with the JSON
API specification. Any non-compliant requests will receive a `4xx` HTTP response containing JSON API
[error objects](http://jsonapi.org/format/#errors) describing how the request is not compliant.

In addition, each resource can have a validators class that defines your application-specific 
validation rules for requests.

## Compliance Validation

JSON API requests to the controller actions provided by this package are automatically checked for compliance
with the JSON API specification. 

As an example, this request:

```http
PATCH /api/posts/123 HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "id": 123,
    "attributes": {
      "title": "Hello World"
    }
  }
}
```

Will be rejected because the specification states that resource ids *must* be strings. This package
will result in the following response:

```http
HTTP/1.1 400 Bad Request
Content-Type: application/vnd.api+json

{
  "errors": [
    [
      "title": "Non-Compliant JSON API Document",
      "status": "400",
      "detail": "The member id must be a string.",
      "source": {
        "pointer": "/data/id"
      }
    ]
  ]
}
```

The response may contain multiple error objects if there are a number of non-compliance problems with
the request.

## Application Specific Validation

This package allows you to define application-specific validation rules for each resource type. This
is implemented on a `Validators` class. The validators allow you to define both rules for the resource
object for create and update requests, plus rules for the query parameters.

Application-specific validation rules are run **after** validation against the JSON API specification,
and will not run unless the request passes the specification checks.

Validators are optional. If you do not define one for your resource type, only the JSON API compliance
validation will be run.

### Generating Validators

To generate validators for a resource type, use the following command:

```bash
$ php artisan make:json-api:validators <resource-type> [<api>]
``` 

> The same validators class is used for both Eloquent and generic resources.

This will generate the following:

```php
<?php

namespace App\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{

    /**
     * The include paths a client is allowed to request.
     *
     * @var string[]|null
     *      the allowed paths, an empty array for none allowed, or null to allow all paths.
     */
    protected $allowedIncludePaths = [];

    /**
     * The sort field names a client is allowed send.
     *
     * @var string[]|null
     *      the allowed fields, an empty array for none allowed, or null to allow all fields.
     */
    protected $allowedSortParameters = [];

    /**
     * Get resource validation rules.
     *
     * @param mixed|null $record
     *      the record being updated, or null if creating a resource.
     * @return mixed
     */
    protected function rules($record = null): array
    {
        return [
            //
        ];
    }

    /**
     * Get query parameter validation rules.
     *
     * @return array
     */
    protected function queryRules(): array
    {
        return [
            //
        ];
    }

}
```

## Resource Object Validation

Resource objects are validated using [Laravel validations](https://laravel.com/docs/validation). If any field
fails the validation rules, a `422 Unprocessable Entity` response will be sent. JSON API errors will be included
containing the Laravel validation messages in the `detail` member of the error object. Each error will also 
have a JSON source point set identifying the location in the request content of the validation failure.

### Creating Resources

Validators are provided with the [resource fields](http://jsonapi.org/format/#document-resource-object-fields)
that were submitted by the client. Collectively these are the `type`, `id`, `attributes` and `relationships`
of the resource. To make it easier to write validation rules, we set the value of relationship fields to the
`data` member of the relationship.

This is best illustrated with an example. Given this request:

```http
POST /api/posts HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "attributes": {
      "title": "Hello World",
      "content": "..."
    },
    "relationships": {
      "author": {
        "data": {
          "type": "users",
          "id": "123"
        }
      },
      "tags": {
        "data": [
          {
            "type": "tags",
            "id": "1"
          },
          {
            "type": "tags",
            "id": "3"
          }
        ]
      }
    }
  }
}
```

Your validator will be provided with the following array of data:

```php
[
    "type" => "posts",
    "id" => null,
    "title" => "Hello World",
    "content" => "...",
    "author" => ["type" => "users", "id" => "123"],
    "tags" => [
        ["type" => "tags", "id" => "1"],
        ["type" => "tags", "id" => "3"],
    ],
];
```

### Updating Resources

When updating resources, the JSON API specification says:

> If a request does not include all of the attributes for a resource, the server MUST interpret
the missing attributes as if they were included with their current values.
The server MUST NOT interpret missing attributes as null values.

As Laravel provides validation rules that allow you to compare values that are being validated (e.g.
a date that must be `before` another value), we take the existing attributes of your resource and merge
the attributes provided by the client over the top.

For example, given this request:


```http
PATCH /api/posts/1 HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "id": "1",
    "attributes": {
      "title": "Hello World"
    },
    "relationships": {
      "tags": {
        "data": [
          {
            "type": "tags",
            "id": "1"
          }
        ]
      }
    }
  }
}
```

If your `posts` resource had a `content` and `published` attributes that were not provided by
the client, your validator will be provided with the following array of data:

```php
[
    "type" => "posts",
    "id" => "1",
    "title" => "Hello World",
    "content" => "...",
    "published" => true,
    "tags" => [
        ["type" => "tags", "id" => "1"],
    ],
];
```

> We use your resource schema's `getAttributes` method to obtain the existing attribute values.

There is no reliable way for us to work out the existing values of any relationships that were missing in
the request document. If you need to add any existing values, you can do this as follows:

```php
class Validators extends AbstractValidators
{
    // ...

    /**
     * @param \App\Post $record
     * @return \Illuminate\Support\Collection
     */
    protected function existingValues($record): Collection
    {
        return parent::existingValues($record)
            ->put('author', ['type' => 'users', 'id' => $record->user_id]);
    }
}
```

### Defining Rules 

Define resource object validation rules in your validators `rules` method. 
This method receives either the record being updated, or `null` for a create request. For example:

```php
class Validators extends AbstractValidators
{
    // ...

    protected function rules($record = null): array
    {
        return [
            'title' => "required|string|min:3",
            'content' => "required|string",
            'author.type' => 'in:users',
        ];
    }

}
```

> When validating the request document for compliance with the JSON API spec, we check that all
relationships contain identifiers for resources that exist in your API. This means that when writing
rules for fields that are relationships, you do not need to check if the `id` provided exists. However
you must validate the `type` of a related resource to ensure it is the expected resource type for
the relationship. As shown in the above example, the related `author` is checked to ensure that it is
a `users` resource.

### Custom Error Messages

To add any custom error messages for your resource object rules, define them on the `$messages` property:

```php
class Validators extends AbstractValidators
{
    // ...

    protected $messages = [
        'title.required' => 'Your post must have a title.',
    ];

}
```

Alternatively you can overload the `messages` method. Like the `rules` method, this receives the
record being updated or `null` for a create request.

### Custom Attribute Names

To define any custom attribute names, add them to the `$attributes` property on your validators:

```php
class Validators extends AbstractValidators
{
    // ...

    protected $attributes = [
        'email' => 'email address',
    ];

}
```

Alternatively you can overload the `attributes` method. Like the `rules` method, this receives the
record being updated or `null` for a create request.

### Conditionally Adding Rules

If you need to [conditionally add rules](https://laravel.com/docs/validation#conditionally-adding-rules), you can
do this by overloading either the `create` or `update` methods. For example:

```php
class Validators extends AbstractValidators
{
    // ...
    
    /**
     * @param array $document
     * @return \CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface
     */
    public function create(array $document): ValidatorInterface
    {
        $validator = parent::create($document);
    
        $validator->sometimes('reason', "required|max:500", function ($input) {
            return $input->games >= 100;
        });
        
        return $validator;
    }

}
```

## Relationship Validation

The JSON API specification provides relationship endpoints for modifying resource relations. To-one and to-many
relationships can be replaced using a `PATCH` request. For to-many relationships, resources can be added
to the relationship via a `POST` request or removed using a `DELETE` request.

### Validation Data and Rules

Given this request:

```http
PATCH /api/posts/1/relationships/tags HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": [
    {
      "type": "tags",
      "id": "1"
    },
    {
      "type": "tags",
      "id": "6"
    }
  ]
}
```

Your validator will be provided with the following array of data:

```php
[
    "type" => "posts",
    "id" => "1",
    "tags" => [
        ["type" => "tags", "id" => "1"],
        ["type" => "tags", "id" => "3"],
    ],
];
```

This data will be passed to a validator that only receives any rules that are for the `tags` field. I.e.
we filter the resource rules returned from your `rules()` method to only include rules that have a key
starting with `tags`.

> If you need to customise the rules used to validate relationships, overload the `relationshipRules()`
method on your validators class.

Custom validation messages (on the `$messages` property) and attribute names (on the `$attribtues` property)
are also passed to your validator.

## Query Validation

In addition to defining rules for validating the JSON API document sent by a client, your validators
class also holds rules for validating query parameters. This allows you to:

- Whitelist expected `filter`, `include`, `page`, `sort` and `fields` parameters.
- Validate `filter`, `page` and non-JSON API query parameters using Laravel validation rules.

### Relationship Query Parameters

One important thing to note with query parameter validation is that when fetching relationships, the
validators class for the related resource will be used. As an example, when a client submits
this request:

```http
GET /api/posts/1/relationships/tags HTTP/1.1
Accept: application/vnd.api+json
```

The query parameters are validated using the `tags` validators class, because the response will contain
`tags` resources, not `posts` resources. Filters, pagination, etc all therefore need to be valid for `tags`.

For this to work, we need to know the inverse resource type when you specify relationship routes. By default
we assume that the inverse resource type is the pluralised form of the relationship name. E.g. for an
`author` relationship we assume the inverse resource type is `authors`. If it was actually `users`, you
need to specify this when defining the relationship route. For example:

```php
JsonApi::register('default', ['namespace' => 'Api'], function ($api, $router) {
    $api->resource('posts', [
        'has-one' => [
            'author' => ['inverse' => 'users'],
        ],
    ]);
});
```

### Whitelisting Parameters

Expected parameters can be defined using any of the following properties on your validators class:

- `$allowedFilteringParameters`
- `$allowedIncludePaths`
- `$allowedPagingParameters`
- `$allowedSortParameters`
- `$allowedFieldSets`

The default values for each of these and how to customise them is discussed in the 
[Filtering](../fetching/filtering.md), [Inclusion](../fetching/inclusion.md), 
[Pagination](../fetching/pagination.md), [Sorting](../fetching/sorting.md) and
[Sparse Fieldsets](../fetching/sparse-fieldsets.md) chapters.

### Defining Rules

In addition to whitelisting parameters you can also use Laravel validation rules for the query
parameters. To define these, add them to your `queryRules()` method. For example:

```php
class Validators extends AbstractValidators
{
    // ...

    protected function queryRules(): array
    {
        return [
            'filter.author' => 'exists:users',
            'page.number' => 'integer|min:1',
            'page.size' => 'integer|between:1,100',
            'foobar' => 'in:baz,bat',
        ];
    }

}
```

### Custom Error Messages

To add any custom error messages for your query parameter rules, define them on the `$queryMessages` property:

```php
class Validators extends AbstractValidators
{
    // ...

    protected $queryMessages = [
        'fitler.author.exists' => 'The author does not exist.',
    ];

}
```

Alternatively you can overload the `queryMessages` method.

### Custom Attribute Names

To define any custom attribute names, add them to the `$queryAttributes` property on your validators:

```php
class Validators extends AbstractValidators
{
    // ...

    protected $queryAttributes = [
        'filter.author' => "the post's author",
    ];

}
```

Alternatively you can overload the `queryAttributes` method.
