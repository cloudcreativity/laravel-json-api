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

### Resource ID

By default, the Eloquent adapter expects the model key that is used for the resource `id` to be the
model's primary key - i.e. the value returned from `Model::getKeyName()`. You can easily change this
behaviour by setting the `$primaryKey` attribute on your adapter.

For example, if we were to use the `slug` model attribute as our resource `id`:

```php
class Adapter extends AbstractAdapter
{
    protected $primaryKey = 'slug';

    // ...
}
```

### Relationships

The Eloquent adapter provides a syntax for defining JSON API resource relationships that is similar to that used
for Eloquent models. The relationship types available are `belongsTo`, `hasOne`, `hasMany` and `morphMany`.
These map to Eloquent relations as follow:

| Eloquent | JSON API |
| :-- | :-- |
| `hasOne` | `hasOne` |
| `belongsTo` | `belongsTo` |
| `hasMany` | `hasMany` |
| `belongsToMany` | `hasMany` |
| `hasManyThrough` | `hasMany` |
| `morphTo` | `belongsTo` |
| `morphMany` | `hasMany` |
| `morphToMany` | `hasMany` |
| `morphedByMany` | `morphMany` |

As relationships are not typically defined as *fillable* on Eloquent models, you must define which relations
are fillable on your adapter. This is done by listing fillable relations in your `$relationships` property, using
the field name of the JSON API relation. For example:

```php
class Adapter extends AbstractAdapter
{

    /**
     * Resource relationship fields that can be filled.
     *
     * @var array
     */
    protected $relationships = ['author', 'tags'];

    // ...
}
```

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

### Has-Many

The JSON API `hasMany` relation can be used for an Eloquent `hasMany`, `belongsToMany`, `hasManyThrough`,
`morphMany` and `morphToMany` relation. For example, if our `posts` resource has a `tags` relationship:

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

> The `morphMany` implementation currently has some limitations that we are hoping to resolve during our alpha
and beta releases. If you have problems using it, please create an issue as this will help us out.
