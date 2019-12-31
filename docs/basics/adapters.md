# Adapters

## Introduction

Adapters define how to query and update your application's storage layer that holds your domain records
(typically a database). Effectively they translate JSON API requests into storage read and write operations.
This package expects there to be an adapter for every JSON API resource type, because some of the logic of how
to query and update domain records in your storage layer will be specific to each resource type.

This package provides an Eloquent adapter for resource types that relate to Eloquent models. However it supports
any type of application storage through an adapter interface.

## Eloquent Adapters

### Generating an Adapter

To generate an adapter for an Eloquent resource type, use the following command:

```
$ php artisan make:json-api:adapter -e <resource-type> [<api>]
```

> The `-e` option does not need to be included if your API configuration has its `use-eloquent` option set
to `true`.

For example, this would create the following for a `posts` resource:

```php
namespace App\JsonApi\Posts;

use App\Post;
use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;
use CloudCreativity\LaravelJsonApi\Pagination\StandardStrategy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class Adapter extends AbstractAdapter
{

    /**
     * Mapping of JSON API attribute field names to model keys.
     *
     * @var array
     */
    protected $attributes = [];

    /**
     * Resource relationship fields that can be filled.
     *
     * @var array
     */
    protected $relationships = [];

    /**
     * Adapter constructor.
     *
     * @param StandardStrategy $paging
     */
    public function __construct(StandardStrategy $paging)
    {
        parent::__construct(new Post(), $paging);
    }

    /**
     * @param Builder $query
     * @param Collection $filters
     * @return void
     */
    protected function filter($query, Collection $filters)
    {
        // TODO
    }

}
```

> The `StandardStrategy` that is injected into the adapter's constructor defines how to page queries for
the resource, and is explained in the [Pagination](../fetching/pagination.md) chapter. The Eloquent adapter
also handles [Filtering](../fetching/filtering.md), [Sorting](../fetching/sorting.md) and eager loading
when [Including Related Resources](../fetching/inclusion.md). Details can be found in the relevant chapters.

### Resource ID

By default, the Eloquent adapter expects the model key that is used for the resource `id` to be the
model's route key - i.e. the key returned from `Model::getRouteKeyName()`. You can easily change this
behaviour by setting the `$primaryKey` attribute on your adapter.

For example, if we were to use the `slug` model attribute as our resource `id`:

```php
class Adapter extends AbstractAdapter
{
    protected $primaryKey = 'slug';

    // ...
}
```

If your resource supports client-generated ids, refer to the client-generated ids section in the
[Creating Resources chapter.](../crud/creating.md)

### Attributes

When filling a model with attributes received in a JSON API request, the adapter will convert the JSON API
field name to either the snake case or camel case equivalent. For example, if your JSON API resource had
an attribute field called `published-at`, this is mapped to `published_at` if your model uses snake case keys,
or `publishedAt` if not.

> We work out whether your model uses snake case or camel case keys based on your model's `$snakeAttributes`
static property.

If you have a JSON API field name that needs to map to a different model attribute, this can be defined in your
adapter's `$attributes` property. For example, if the `published-at` field needed to be mapped to the
`published_date` attribute on your model, it must be defined as follows:

```php
class Adapter extends AbstractAdapter
{
    protected $attributes = [
        'published-at' => 'published_date',
    ];

    // ...
}
```

#### Mass Assignment

All attributes received in a JSON API resource from a client will be filled into your model, as we assume that
you will protect any attributes that are not fillable using Eloquent's
[mass assignment](https://laravel.com/docs/eloquent#mass-assignment) feature.

There may be cases where an attribute is fillable on your model, but you do not want to allow your JSON API to
fill it. You can set your adapter to skip attributes received from a client by listing the JSON API
field name in the `$guarded` property on your adapter. For example, if we did not want the `published-at` field
to be filled into our model, we would define it as follows:

```php
class Adapter extends AbstractAdapter
{
    protected $guarded = ['published-at'];

    // ...
}
```

Alternatively, you can white-list JSON API fields that can be filled by adding them to the `$fillable` property
on your adapter. For example, if we only wanted the `title`, `content` and `published-at` fields to be filled:

```php
class Adapter extends AbstractAdapter
{
    protected $fillable = ['title', 'content', 'published-at'];

    // ...
}
```

> Need to programmatically work out the list of fields that are fillable or guarded? Overload the `getGuarded` or
`getFillable` methods.

#### Dates

By default the adapter will cast values to date time objects based on whether the model attribute it is filling
is defined as a date. The adapter uses `Model::getDates()` to work this out.

Alternatively, you can list all the JSON API field names that must be cast as dates in the `$dates` property on
your adapter. For example:

```php
class Adapter extends AbstractAdapter
{
    protected $dates = ['created-at', 'updated-at', 'published-at'];

    // ...
}
```

Dates are cast to `Carbon` instances. You can change this by overloading the `deserializeDate` method. For
example, if we wanted the date time to be interpreted with a timezone that was obtainable from the model:

```php
/**
 * @param $value the value submitted by the client.
 * @param string $field the JSON API field name being deserialized.
 * @param Model $record the domain record being filled.
 * @return \DateTime|null
 */
public function deserializeDate($value, $field, $record)
{
    return $value ? new \DateTime($value, $record->getTimeZone()) : null;
}
```

#### Mutators

If you need to convert any values as they are being filled into your Eloquent model, you can use
[Eloquent mutators](https://laravel.com/docs/eloquent-mutators#defining-a-mutator).

However, if there are cases where your conversion is unique to your JSON API or not appropriate on your model,
the adapter allows you to implement mutator methods. These must be called `deserializeFooField`, where `Foo`
is the name of the JSON API attribute field name.

For example, if we had a JSON API `currency` attribute that must always be filled in uppercase:

```php
class Adapter extends AbstractAdapter
{
    // ...

    protected function deserializeCurrencyField($value)
    {
        return strtoupper($value);
    }
}
```

### Relationships

The Eloquent adapter provides a syntax for defining JSON API resource relationships that is similar to that used
for Eloquent models. The relationship types available are `belongsTo`, `hasOne`, `hasMany`, `hasManyThrough`,
`morphMany`, `queriesOne` and `queriesMany`. These map to Eloquent relations as follow:

| Eloquent | JSON API |
| :-- | :-- |
| `hasOne` | `hasOne` |
| `hasOneThrough` | `hasOneThrough` |
| `belongsTo` | `belongsTo` |
| `hasMany` | `hasMany` |
| `belongsToMany` | `hasMany` |
| `hasManyThrough` | `hasManyThrough` |
| `morphTo` | `belongsTo` |
| `morphMany` | `hasMany` |
| `morphToMany` | `hasMany` |
| `morphedByMany` | `morphMany` |
| n/a | `queriesOne` |
| n/a | `queriesMany` |

All relationships that you define on your adapter are treated as fillable by default when creating or updating
a resource object. If you want to prevent a relationship from being filled, add the JSON API field name to your
`$fillable` or `$guarded` adapter properties as described above in *Mass Assignment*.

#### Belongs-To

The JSON API `belongsTo` relation can be used for an Eloquent `belongsTo` or `morphTo` relation. The relation
is defined in your adapter as follows:

```php
class Adapter extends AbstractAdapter
{
    // ...

    protected function author()
    {
        return $this->belongsTo();
    }
}
```

By default this will assume that the Eloquent relation name is the same as the JSON API relation name - `author`
in the example above. If this is not the case, you can provide the Eloquent relation name as the first function
argument.

For example, if our JSON API `author` relation related to the `user` model relation:

```php
class Adapter extends AbstractAdapter
{
    // ...

    protected function author()
    {
        return $this->belongsTo('user');
    }
}
```

#### Has-One

Use the `hasOne` relation for an Eloquent `hasOne` relation. This has the same syntax as the `belongsTo` relation.
For example if a `users` JSON API resource had a has-one `phone` relation:

```php
class Adapter extends AbstractAdapter
{
    // ...

    protected function phone()
    {
        return $this->hasOne();
    }
}
```

This will assume that the Eloquent relation on the model is also called `phone`. If this is not the case, pass
the Eloquent relation name as the first function argument:

```php
class Adapter extends AbstractAdapter
{
    // ...

    protected function phone()
    {
        return $this->hasOne('cell');
    }
}
```

#### Has-Many

The JSON API `hasMany` relation can be used for an Eloquent `hasMany`, `belongsToMany`, `morphMany` and
`morphToMany` relation. For example, if our `posts` resource has a `tags` relationship:

```php
class Adapter extends AbstractAdapter
{
    // ...

    protected function tags()
    {
        return $this->hasMany();
    }
}
```

This will assume that the Eloquent relation on the model is also called `tags`. If this is not the case, pass
the Eloquent relation name as the first function argument:

```php
class Adapter extends AbstractAdapter
{
    // ...

    protected function tags()
    {
        return $this->hasMany('categories');
    }
}
```

#### Has-One-Through and Has-Many-Through

The JSON API `hasOneThrough` and `hasManyThrough` relations can be used for an Eloquent `hasOneThrough`
and `hasManyThrough` relation. The important thing to note about these relationships is that both are **read-only**.
This is because the relationship can be modified in your API by modifying the intermediary model.
For example, a `countries` resource might have many `posts` resources through an intermediate `users` resource.
The relationship is effectively modified by creating and deleting posts and/or a user changing which country they
are associated to.

Use the `hasOneThrough()` or `hasManyThrough()` methods on your adapter as follows:

```php
class Adapter extends AbstractAdapter
{
    // ...

    protected function posts()
    {
        return $this->hasManyThrough();
    }
}
```

This will assume that the Eloquent relation on the country model is also called `posts`. If this is not the case,
pass the Eloquent relation name as the first function argument:

```php
class Adapter extends AbstractAdapter
{
    // ...

    protected function posts()
    {
        return $this->hasManyThrough('publishedPosts');
    }
}
```

#### Morph-Many

Use the JSON API `morphMany` relation for an Eloquent `morphedByMany` relation. The `morphMany` relation in effect
*mixes* multiple different JSON API resource relationships in a single relationship.

This is best demonstrated with an example. If our application has a `tags` resource that can be linked to either
`videos` or `posts`, our `tags` adapter would define a `taggables` relation as follows:

```php
class Adapter extends AbstractAdapter
{
    // ...

    protected function taggables()
    {
        return $this->morphMany(
            $this->hasMany('posts'),
            $this->hasMany('videos')
        );
    }
}
```

> The `morphMany` implementation currently has some limitations that we are hoping to resolve during future
releases. If you have problems using it, please create an issue as this will help us out.

#### Queries-One and Queries-Many

Use the `queriesOne` or `queriesMany` relations when you want to expose a JSON API relationship that uses an
Eloquent query builder instead of an Eloquent relation.

For example, if our `Post` model had the following query scope:

```php
namespace App;

use Illuminate\Database\Eloquent\Model;

class Post extends Model
{

    // ...

    /**
     * Scope a query for posts that are related to the supplied post.
     *
     * Related posts are those that:
     *
     * - have a tag in common with the provided post; or
     * - are by the same author.
     *
     * @param Builder $query
     * @param Post $post
     * @return Builder
     */
    public function scopeRelated(Builder $query, Post $post)
    {
        return $query->where(function (Builder $q) use ($post) {
            $q->whereHas('tags', function (Builder $t) use ($post) {
                $t->whereIn('tags.id', $post->tags()->pluck('tags.id'));
            })->orWhere('posts.author_id', $post->getKey());
        })->where('posts.id', '<>', $post->getKey());
    }
}
```

We can expose this scope as a JSON API relationship called `related` on our `posts` resource by adding
the following to our `posts` adapter:

```php
namespace App\JsonApi\Posts;

class Adapter extends AbstractAdapter
{
    // ...

    protected function related()
    {
        return $this->queriesMany(function (Post $post) {
            return Post::query()->related($post);
        });
    }
}
```

This will return a JSON API `to-many` relationship based on the Eloquent query builder returned by the
closure. The `queriesOne()` relationship works in exactly the same way, but returns a `to-one` JSON API
relationship.

Note that the `queriesOne` and `queriesMany` relations are read-only and result in an error if you define
any of the modify relationship routes. If you have a scenario where a client could modify one of these
relationships, extend either of the following classes and add logic to the relevant methods:

- `CloudCreativity\LaravelJsonApi\Eloquent\QueriesOne`
- `CloudCreativity\LaravelJsonApi\Eloquent\QueriesMany`

#### Customising Relation Method Names

If you want to use a method name for your relation that is different than the JSON API field name, overload
the `methodForRelation` method on your adapter. For example, you would need to do this if the field name collides
with a method that already exists on the abstract adapter.

The method can be overloaded as follows:

```php
protected function methodForRelation($field)
{
  if ('my-field' === $field) {
    return 'myOtherMethodName';
  }

  return parent::methodForRelation($field);
}
```

### Soft-Deleting

By default the Eloquent resource adapter uses the model's `delete` method when the client sends a `DELETE`
request. It also does not find soft-deleted models for a `GET` request. This package provides a trait to
modify this default behaviour and expose soft-deleting capabilities to the client.

See the [Soft Deleting](../features/soft-deletes.md) for information on implementing this.

### Scopes

Eloquent adapters allow you to apply scopes to your API resources, using Laravel's
[global scopes](https://laravel.com/docs/eloquent#global-scopes) feature. When a scope is applied
to an Eloquent adapter, any routes that return that API resource in the response content will have
the scope applied.

> An example use case for this would be if your API only contains resources related to the current signed
in user. In this case, you would only ever want resources owned by that user to appear in the API. I.e.
from the client's perspective, any resources belonging to other users do not exist. In this case, a global
scope would ensure that a `404 Not Found` is returned for resources that belong to other users.

> In contrast, if your API serves a mixture of resources belonging to different users, then 
`401 Unauthorized` or `403 Forbidden` responses might be more appropriate when attempting to access other
users' resources. In this scenario, [Authorizers](./security.md) would be a better approach than global
scopes.

Scopes can be added to an Eloquent adapter as either scope classes or as closure scopes. To use the former,
write a class that implements Laravel's `Illuminate\Database\Eloquent\Scope` interface. The class can then
be added to your adapter using constructor dependency injection and the `addScopes` method:

```php
namespace App\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;

class Adapter extends AbstractAdapter
{

    public function __construct(\App\Scopes\UserScope $scope)
    {
      parent::__construct(new \App\Post());
      $this->addScopes($scope);
    }

    // ...
}
```

> Using a class scope allows you to reuse that scope across multiple adapters.

If you prefer to use a closure for your scope, these can be added to an Eloquent adapter using the
`addClosureScope` method. For example, in our `AppServiceProvider::register()` method:

```php
$this->app->afterResolving(\App\JsonApi\Posts\Adapter::class, function ($adapter) {
  $adapter->addClosureScope(function ($query) {
    $query->where('author_id', \Auth::id());
  });
});
```

## Custom Adapters

Custom adapters can be used for any domain record that is not an Eloquent model. Adapters will work with this
package as long as they implement the `CloudCreativity\LaravelJsonApi\Contracts\Adapter\ResourceAdapterInterface`.
We have also provided an abstract class to extend that contains some of the logic that is used in our Eloquent
adapter.

> If a lot of your domain records use the same persistence layer, it is likely you can write your own abstract
adapter class to handle those domain records generically. For example, if you were using Doctrine you could write
an abstract Doctrine adapter. We recommend looking our generic Eloquent adapter as an example.

### Generating an Adapter

To generate a custom adapter that extends the package's abstract adapter, use the following command:

```
$ php artisan make:json-api:adapter -N <resource-type> [<api>]
```

> The `-N` option does not need to be included if your API configuration has its `use-eloquent` option set
to `false`.

For example, this would create the following for a `posts` resource:

```php
namespace App\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Adapter\AbstractResourceAdapter;
use CloudCreativity\LaravelJsonApi\Document\ResourceObject;
use Illuminate\Support\Collection;
use Neomerx\JsonApi\Contracts\Encoder\Parameters\EncodingParametersInterface;

class DummyClass extends AbstractResourceAdapter
{

    /**
     * @inheritDoc
     */
    protected function createRecord(ResourceObject $resource)
    {
        // TODO: Implement createRecord() method.
    }

    /**
     * @inheritDoc
     */
    protected function fillAttributes($record, Collection $attributes)
    {
        // TODO: Implement fillAttributes() method.
    }

    /**
     * @inheritDoc
     */
    protected function persist($record)
    {
        // TODO: Implement persist() method.
    }
  
    /**
     * @inheritDoc
     */  
    protected function destroy($record)
    {
        // TODO: Implement destroy() method.
    }

    /**
     * @inheritDoc
     */
    public function query(EncodingParametersInterface $parameters)
    {
        // TODO: Implement query() method.
    }

    /**
     * @inheritDoc
     */
    public function exists($resourceId)
    {
        // TODO: Implement exists() method.
    }

    /**
     * @inheritDoc
     */
    public function find($resourceId)
    {
        // TODO: Implement find() method.
    }

    /**
     * @inheritDoc
     */
    public function findMany(array $resourceIds)
    {
        // TODO: Implement findMany() method.
    }

}
```

The methods to implement are documented on the `ResourceAdapterInterface` and the `AbstractResourceAdapter`.

### Relationships

You can add support for any kind of relationship by writing a class that implements either:

- `CloudCreativity\LaravelJsonApi\Contracts\Adapter\RelationshipAdapterInterface` for *to-one* relations.
- `CloudCreativity\LaravelJsonApi\Contracts\Adapter\HasManyAdapterInterface` for *to-many* relations.

We provide a base abstract class that you can extend:
`CloudCreativity\LaravelJsonApi\Adapter\AbstractRelationshipAdapter`.
This implements the `to-one` interface. If your relation is a `to-many` relation, just extend the same
abstract class and implement the `HasManyAdapterInterface`.

Refer to the doc blocks on the interfaces for the methods that you need to implement.
If you use a common persistence layer you are likely to find that you can write generic classes to
handle specific *types* of relationships. For examples see the Eloquent relation classes that are in the
`CloudCreativity\LaravelJsonApi\Eloquent` namespace.

If you are extending the abstract adapter provided by this package, you can define relationships on your resource
adapter in the same way as the Eloquent adapter. For example:

```php
class Adapter extends AbstractAdapter
{
    // ...

    protected function author()
    {
        return new MyCustomRelation();
    }
}
```

## Adapter Hooks

Adapter provide a number of hooks that allow you to perform custom filling logic when a resource is being
created or updated.

When a resource is being created, the `saving`, `creating`, `created` and `saved` hooks will be invoked.
For an update, the `saving`, `updating`, `updated` and `saved` hooks are invoked. All these hooks receive the
record as the first argument, and the resource object sent by the client as the second argument.

For example, if we wanted to store the user creating a comment resource, we could use the `creating` hook
on our adapter:

```php
class Adapter extends AbstractAdapter
{
    // ...
    
    protected function creating(Comment $comment): void
    {
        $comment->createdBy()->associate(Auth::user());
    }
}
```

> If your resource uses a [client-generated ID](../crud/creating.md#client-generated-ids), you 
will need to use the `creating` hook to assign the id to the model.

There are two additional hooks that are invoked when an adapter is deleting a resource: `deleting` and `deleted`.
These receive the record being deleted as the first function argument.

> As the adapter is the place where records are filled with values provided by a client, adapter hooks are
primarily intended for additional *filling* logic. I.e. to fill values that are not provided by the client.
[Controllers](./controllers.md) provided an extensive list of hooks that are intended for dispatching jobs
and events. Additionally, you can use [Asynchronous Processing](../features/async.md) for complex job
processing.
