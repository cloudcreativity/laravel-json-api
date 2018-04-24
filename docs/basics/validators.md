# Validation

## Introduction

Each resource can have a validators class that defines the validation rules for resource create and update
operations. It also validates query parameters for a JSON API request.

> We are planning to improve validators before the final `1.0.0` release, so this chapter provides a brief
explanation of the current implementation.

## Generating Validators

To generate validators for a resource type, use the following command:

```bash
$ php artisan make:json-api:validators <resource-type> [<api>]
``` 

> The same validators class is used for both Eloquent and generic resources.

This will generate the following:

```php
namespace App\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Contracts\Validators\RelationshipsValidatorInterface;
use CloudCreativity\LaravelJsonApi\Validators\AbstractValidatorProvider;

class Validators extends AbstractValidatorProvider
{

    /**
     * @var string
     */
    protected $resourceType = 'posts';

    /**
     * Get the validation rules for the resource attributes.
     *
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     * @return array
     */
    protected function attributeRules($record = null)
    {
        return [
            //
        ];
    }

    /**
     * Define the validation rules for the resource relationships.
     *
     * @param RelationshipsValidatorInterface $relationships
     * @param object|null $record
     *      the record being updated, or null if it is a create request.
     * @return void
     */
    protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
    {
        //
    }

}
```

> The `Validators` class also validates query parameters in requests for the resource type that it relates
to. Validating query parameters is explained in the [Pagination](../fetching/pagination.md),
[Filtering](../fetching/filtering.md), [Sorting](../fetching/sorting.md), 
[Including Related Resources](../fetching/inclusion.md) and [Sparse Fieldsets](../fetching/sparse-fieldsets.md)
chapters.

## Attributes

Attributes are validated using [Laravel validations](https://laravel.com/docs/validation). If any attributes
fail the validation rules, a `422 Unprocessable Entity` response will be sent. JSON API errors will be included
containing the Laravel validation messages and each error will have its pointer set to the correct attribute.

### Rules 

Laravel validation rules for attributes are returned from your `attributeRules` method. 
This method receives either the record being updated, or `null` for a create request. For example:

```php
class Validators extends AbstractValidatorProvider
{
    // ...

    protected function attributeRules($record = null)
    {
        $required = $record ? 'filled' : 'required';
    
        return [
            'title' => "$required|string|min:3",
            'content' => "$required|string",
        ];
    }

}
```

The JSON API spec defines that an update request must interpret the omission of any resource attributes as if
they were included with their current values. We therefore use the 
[filled rule](https://laravel.com/docs/validation#rule-filled) for any required attributes on an update request.

### Custom Error Messages

To add any custom error messages for your attribute rules, define them on the `$messages` property:

```php
class Validators extends AbstractValidatorProvider
{
    // ...

    protected $messages = [
        'title.required' => 'Your post must have a title.',
    ];

}
```

Alternatively you can overload the `attributeMessages` method. Like the `attributeRules` method, this receives the
record being updated or `null` for a create request.

### Custom Attribute Names

To define any custom attribute names, add them to the `$customAttributes` property on your validators:

```php
class Validators extends AbstractValidatorProvider
{
    // ...

    protected $customAttributes = [
        'email' => 'email address',
    ];

}
```

Alternatively you can overload the `attributeCustomAttributes` method.

### Conditionally Adding Rules

If you need to [conditionally add rules](https://laravel.com/docs/validation#conditionally-adding-rules), you can
do this in the `conditionalAttributes` method. This receives the Laravel validator instance as its first argument,
and the record being updated (or `null` for a create request) as its second argument:

```php
use Illuminate\Contracts\Validation\Validator;

class Validators extends AbstractValidatorProvider
{
    // ...

    protected function conditionalAttributes(Validator $validator, $record = null)
    {
        $required = $record ? 'filled' : 'required';
    
        $validator->sometimes('reason', "$required|max:500", function ($input) {
            return $input->games >= 100;
        });
    }

}
```

Note that the input passed to the closure will be the content of the `attributes` member of the JSON API resource.

### Extracting Attributes

By default the validators class will run the attributes validation against all attributes in the JSON API resource
submitted by the client. However this can be overridden if needed.

A common use case for overriding this behaviour is for validating update operations. As the JSON API spec states
that the server must interpret any missing attributes as if they were included with their current value, you
may need to include the current value for validation purposes.

As an example, if you have an `events` resource that had a `starts-at` and `ends-at` attribute with the following
validation rules:

```php
class Validators extends AbstractValidatorProvider
{
    // ...

    protected function attributeRules($record = null)
    {
        $required = $record ? 'filled' : 'required';
    
        return [
            'starts-at' => "$required|date|before:ends-at",
            'ends-at' => "$required|date|after:starts-at",
        ];
    }
}
```

This validation will only work if both the `starts-at` and `ends-at` values are provided, but for an update operation
the spec defines that the current value is to be used if the client omits either.

To solve this we push the current values into the attributes prior to validation using the `extractAttributes`
method:

```php
use CloudCreativity\LaravelJsonApi\Contracts\Object\ResourceObjectInterface;

class Validators extends AbstractValidatorProvider
{
    // ...

    /**
     * Extract attributes for validation from the supplied resource.
     *
     * @param ResourceObjectInterface $resource
     * @param object|null $record
     * @return array
     */
    protected function extractAttributes(ResourceObjectInterface $resource, $record = null)
    {
        return $resource
            ->getAttributes()
            // add only pushes a value into the attributes if it is not already present.
            ->add('starts-at', $record ? $record->starts_at->toDateTimeString() : null)
            ->add('ends-at', $record ? $record->ends_at->toDateTimeString() : null)
            ->toArray();
    }
}
```

## Relationships

We do not use Laravel validators for resource relationships. This is because the JSON API spec defines different
statuses and errors that must be returned for relationships, and we cannot detect these scenarios if we used
Laravel validators.

All relationships sent by a client are validated to check that the related resources actually exist. In 
addition, you can define validation rules for specific relationships in your `relationshipRules` method.
For example:

```php
use CloudCreativity\LaravelJsonApi\Contracts\Validators\RelationshipsValidatorInterface;

class Validators extends AbstractValidatorProvider
{
    // ...

    protected function relationshipRules(RelationshipsValidatorInterface $relationships, $record = null)
    {
        $relationships->hasOne('author', 'users', is_null($record), true);
        $relationships->hasMany('tags', 'tags', false, true);
    }
}
```

As with the attribute methods, `$record` will either be the record being updated or `null` for a create request.

The method signature for both the `hasOne` and `hasMany` methods is as follows:
`hasOne($relationshipName, $expectedResourceType, $required, $allowEmpty)`.

> The above implementation will meet most use-cases but we appreciate it is not ideal for more complex uses cases.
We are planning to improve relationship validation and if you have any particular requirements please create a
Github issue describing your use-case.