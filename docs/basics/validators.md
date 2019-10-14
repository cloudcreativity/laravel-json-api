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
    {
      "title": "Non-Compliant JSON API Document",
      "status": "400",
      "detail": "The member id must be a string.",
      "source": {
        "pointer": "/data/id"
      }
    }
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

## Modify Resource Validation

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

### Basic Rule Example

The following is an example query rule for the above-mentioned data:

```php
use CloudCreativity\LaravelJsonApi\Rules\HasMany;
use CloudCreativity\LaravelJsonApi\Rules\HasOne;
use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{
    // ...

    protected function rules($record = null): array
    {
        return [
            'title' => 'required|string|min:1|max:255',
            'content' => 'required|string|min:1',
            'author' => [
                'required',
                new HasOne('users'),
            ],
            'tags' => new HasMany('tags'),
        ];
    }
}
```

You'll notice that the `exists` rule is not used in the validation for the `author` and `tags` relationships.
This is because the package complies with the JSON API spec and validates all relationship identifiers to
check that they exist. Therefore the following **does not** need to be used:

```php
protected function rules($record = null): array
{
    return [
        'author.id' => 'exists:users,id',
        'tags.*.id' => 'exists:tags,id'
    ];
}
```

The `HasOne` and `HasMany` rules accept a list of resource types for polymorphic relationships. If no
type is provided to the constructor, then the plural form of the attribute name will be used. For
example:

```php
protected function rules($record = null): array
{
    return [
        'author' => [
            'required',
            new HasOne(), // expects 'authors' resources
        ],
        'tags' => new HasMany(), // expects 'tags' resources
    ];
}
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
the request document. If you need to add any existing relationship values, you can do this by returning
the relationships in their JSON API form. For example:

```php
class Validators extends AbstractValidators
{
    // ...

    /**
     * @param \App\Post $record
     * @return iterable
     */
    protected function existingRelationships($record): iterable
    {
        return [
            'author' => $record->user,
        ];
    }
}
```

#### Disabling or Customising Existing Values

If you need to disable the merging of the existing values, set the `$validateExisting` property
of your validators class to `false`. If you need to programmatically work out whether to merge the existing
values, overload the `mustValidateExisting()` method.

If you want to use the merging of existing values, but need to adjust the extraction of current
attributes, you can overload the `existingAttributes` method. For example, if you are using the `not_present`
rule for an attribute, you would not want the existing value to be merged in. In this case you could
forget the existing value as follows:

```php
class Validators extends AbstractValidators
{
    // ...

    /**
     * @param \App\Post $record
     * @return iterable
     */
    protected function existingAttributes($record): iterable
    {
        return collect(parent::existingAttributes($record))->forget('foobar');
    }
}
```

### Defining Rules 

Define resource object validation rules in your validators `rules` method. 
This method receives either the record being updated, or `null` for a create request. For example:

```php
use CloudCreativity\LaravelJsonApi\Rules\HasOne;
use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{
    // ...

    protected function rules($record = null): array
    {
        return [
            'title' => "required|string|min:3",
            'content' => "required|string",
            'author' => [
                'required',
                new HasOne('users'),
            ],
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

## Modify Relationship Validation

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

Custom validation messages (on the `$messages` property) and attribute names (on the `$attributes` property)
are also passed to your validator.

## Delete Resource Validation

It is possible to add validation rules for deleting resources. This is useful if you want to prevent
the deletion of a resource in certain circumstances - for example, if you did not want to allow API clients
to delete posts that have comments.

This validation is optional. If your validators class does not define any delete rules, the delete request
will be allowed.

### Validation Data

By default we pass the resource's current field values to the delete validator, using the 
`existingRelationships` method to work out the values of any relationships. 
(The `existingRelationships` method is discussed above in the update resource validation section.)

If a `posts` resource had a `title` and `content` attributes, given the following validators class:

```php
class Validators extends AbstractValidators
{
    // ...

    /**
     * @param \App\Post $record
     * @return iterable
     */
    protected function existingRelationships($record): iterable
    {
        return [
            'author' => $record->user,
        ];
    }
}
```

The validator would receive this data:

```php
[
    "title": "posts",
    "id": "1",
    "title": "Hello World!",
    "content": "...",
    "author": [
        "type": "users",
        "id": "123"
    ]
]
```

If you wanted to use different data for validating a delete request, overload the `dataForDelete` method.
This is shown in the next example.

### Defining Rules

Define delete validation rules in your validators `deleteRules` method. For example, if we wanted to
stop a `posts` resource from being deleted if it has any comments:

```php
class Validators extends AbstractValidators
{
    // ...
    
    /**
     * @var array
     */
    protected $deleteMessages = [
        'no_comments.accepted' => 'Cannot delete a post with comments.',
    ];

    /**
     * @param \App\Post $record
     * @return array|null
     */
    protected function deleteRules($record): ?array
    {
        return [
            'no_comments' => 'accepted',
        ];
    }

    /**
     * @param \App\Post $record
     * @return array
     */
    protected function dataForDelete($record): array
    {
        return [
            'no_comments' => $record->comments()->doesntExist(),
        ];
    }
}
```

> Returning an empty array or `null` from the `deleteRules` validator indicates that a delete
request does not need to be validated.

### Custom Error Messages

To add any custom error messages for your delete resource rules, define them on the `$deleteMessages` property.
This is shown in the example above.

Alternatively you can overload the `deleteMessages` method. Like the `deleteRules` method, this receives the
record being deleted.

### Custom Attribute Names

To define any custom attribute names for delete resource validation, add them to the `$deleteAttributes`
property on your validators.

Alternatively you can overload the `deleteAttributes` method. Like the `deleteRules` method, this receives the
record being deleted.

### Conditionally Adding Rules

If you need to [conditionally add rules](https://laravel.com/docs/validation#conditionally-adding-rules), you can
do this by overloading the `delete` method. For example:

```php
class Validators extends AbstractValidators
{
    // ...
    
    /**
     * @param \App\Post $record
     * @return array
     */
    protected function dataForDelete($record): array
    {
        return [
            'is_author' => \Auth::user()->is($record->author),
            'no_comments' => $record->comments()->doesntExist(),
        ];
    }
    
    /**
     * @param \App\Post $record
     * @return \CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface
     */
    public function delete($record): ValidatorInterface
    {
        $validator = parent::create($document);
    
        $validator->sometimes('no_comments', 'accepted', function ($input) use ($record) {
            return !$input->is_author;
        });
        
        return $validator;
    }

}
```

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

## Validating Dates

JSON API 
[recommends using the ISO 8601 format for date and time strings in JSON](https://jsonapi.org/recommendations/#date-and-time-fields).
This is not possible to validate using Laravel's `date_format` validation rule, because W3C state that a number of
date and time formats are valid. For example, all of the following are valid:

- `2018-01-01T12:00Z`
- `2018-01-01T12:00:00Z`
- `2018-01-01T12:00:00.123Z`
- `2018-01-01T12:00:00.123456Z`
- `2018-01-01T12:00+01:00`
- `2018-01-01T12:00:00+01:00`
- `2018-01-01T12:00:00.123+01:00`
- `2018-01-01T12:00:00.123456+01:00`

To accept any of the valid formats for a date field, this package provides a rule object: `DateTimeIso8601`. 
This can be used as follows:

```php
use CloudCreativity\LaravelJsonApi\Rules\DateTimeIso8601;

return [
    'published-at' => ['nullable', new DateTimeIso8601()]
];
```

## Required Rule

Using the required rule can result in a JSON API error object with a JSON pointer to either `/data` or the
actual field that is required, e.g. `/data/attributes/content`. This will vary based on whether the client
omits the field or sends an empty value for the field.

If you always want the pointer to relate to the actual field, e.g. `/data/attributes/content`, ensure
your client *always* sends a value for the field, even if that value is empty (e.g. `null`).

To illustrate this, here are two requests that fail the required rule and the resulting error response:

### Field Omitted

```http
POST /api/posts HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "attributes": {
      "title": "Hello World"
    }
  }
}
```

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/vnd.api+json

{
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Entity",
      "detail": "The content field is required.",
      "source": {
        "pointer": "/data"
      }
    }
  ]
}
```

In this scenario, a JSON pointer of `/data/attributes/content` cannot be used as it would point at a field
that does not exist in the request JSON. Instead, the `/data` pointer indicates the error is caused by the
resource object held in the top-level `data` member.

### Field Empty

```http
POST /api/posts HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{
  "data": {
    "type": "posts",
    "attributes": {
      "title": "Hello World",
      "content": null
    }
  }
}
```

```http
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/vnd.api+json

{
  "errors": [
    {
      "status": "422",
      "title": "Unprocessable Entity",
      "detail": "The content field is required.",
      "source": {
        "pointer": "/data/attributes/content"
      }
    }
  ]
}
```

In this scenario, the pointer can be `/data/attributes/content` as the field actually exists in the request
JSON.

## Confirmed Rule

Laravel's `confirmed` rule expects there to be a field with the same name and `_confirmation` on the end: i.e.
if using the `confirmed` rule on the `password` field, it expects there to be a `password_confirmation` field.

If you are not using underscores in your field names, this means the `confirmed` rules will not work. For example
if using dash-case your extra field will be called `password-confirmation`. Unfortunately Laravel does not
provide a way of customising the expected confirmation field name.

In this scenario you will need to use the following rules to get `password-confirmation` working:

```php
namespace App\JsonApi\Users;

use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{
    // ...

    protected function rules($record = null): array
    {
        return [
            'name' => 'required|string',
            'password' => "required|string",
            'password-confirmation' => "required_with:password|same:password",
        ];
    }
}
```

Remember to note the guidance above about `PATCH` requests, where the server must assume that missing values
being the current values. For password scenarios, your validator will not have access to the current value.
You would therefore need to adjust your use of the `required` and `required_with` rules to only add them if
the client has sent a password. For example:

```php
namespace App\JsonApi\Users;

use CloudCreativity\LaravelJsonApi\Contracts\Validation\ValidatorInterface;
use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{
    // ...

    public function update($record, array $document): ValidatorInterface
    {
        $validator = parent::update($record, $document);

        $validator->sometimes('password-confirmation', 'required_with:password|same:password', function ($input) {
            return isset($input['password']);
        });

        return $validator;
    }

    protected function rules($record = null): array
    {
        $rules = [
            'name' => 'required|string',
            'password' => [
                $record ? 'filled' : 'required',
                'string',
            ],
        ];

        if (!$record) {
            $rules['password-confirmation'] = 'required_with:password|same:password';
        }

        return $rules;
    }
}
```

## Failed Rules

This package makes it possible to include a machine-readable reason why a value failed validation within
the JSON API error object's `meta` member. This is an opt-in feature because it is not standard practice
for Laravel to JSON encode validation failure information with validation error messages.

For example, if a value fails to pass the `between` rule, then by default this package will return the
following response content:

```json
{
    "errors": [
        {
            "status": "422",
            "title": "Unprocessable Entity",
            "detail": "The value must be between 1 and 10.",
            "source": {
                "pointer": "/data/attributes/value"
            }
        }
    ]
}
```

If you opt-in to showing failed meta, the response content will be:

```json
{
    "errors": [
        {
            "status": "422",
            "title": "Unprocessable Entity",
            "detail": "The value must be between 1 and 10.",
            "source": {
                "pointer": "/data/attributes/value"
            },
            "meta": {
                "failed": {
                    "rule": "between",
                    "options": [
                        "1",
                        "10"
                    ]
                }
            }
        }
    ]
}
```

The rule name will be the dash-case version of the Laravel rule. For example, `before_or_equal` will
be `before-or-equal`. If the rule is a rule object, we use the dash-case of the class basename.
For example, `CloudCreativity\LaravelJsonApi\Rules\DateTimeIso8601` will be `date-time-iso8601`.

The `options` member will only exist if the rule has options. We intentionally omit rule options
for the `exists` and `unique` rules as the options for these database rules reveal information
about your database setup.

To opt-in to this feature, add the following to the `register` method of your `AppServiceProvider`:

```php
<?php

namespace App\Providers;

use CloudCreativity\LaravelJsonApi\LaravelJsonApi;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register()
    {
        LaravelJsonApi::showValidatorFailures();
    }
    
    // ...
    
}
```
