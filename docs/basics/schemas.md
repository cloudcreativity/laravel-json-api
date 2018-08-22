# Resource Object Schemas

## Introduction

Resource objects are the JSON API representations of your application's domain records (instances of PHP classes).
Every PHP class that can appear in your JSON API documents must have a `Schema` class. This schema defines how
to convert the PHP object into a JSON API resource object.

Credit where credit is due... the encoding of PHP objects to JSON API resources is handled by the
[neomerx/json-api](https://github.com/neomerx/json-api) package.

> In previous versions we had an Eloquent schema. These are deprecated and will be removed for `v2.0.0`.
We therefore recommend that you use generic schemas for any new schemas that you are creating. Documentation
for this deprecated class can be found
[here](https://github.com/cloudcreativity/laravel-json-api/blob/v1.0.0-alpha.1/docs/basics/schemas.md).

## Defining Resources

Your API's configuration contains a list of resources that appear in its JSON API documents in its `resources` array. 
This array maps the JSON API resource object `type` to the PHP class that it relates to. For example:

```php
'resources' => [
    'posts' => App\Post::class,
    'comments' => App\Comment::class,
]

// ...
```

> You need to list **every** resource that can appear in a JSON API document in the `resources` configuration,
even resources that do not have API routes defined for them. This is so that the JSON API encoder 
can locate a schema for each PHP class it encounters.

## Creating Schemas

To generate a schema that extends, use the following command. The generated schema will extend
`Neomerx\JsonApi\Schema\SchemaProvider`.

```bash
php artisan make:json-api:schema <resource-type> [<api>]
```

> If your `use-eloquent` option is set to `true`, the created schema will have some methods already filled.
If you want to generate a non-Eloquent schema, add the `-N` option.

## Identification

Every JSON API resource object must have a `type` and `id` member. To define the `type`, set the `$resourceType`
property on your schema. The `id` is returned by the `getId()` method and the JSON API spec states
that this **must** be a string.

If you have generated your schema, the class will already have the `type` property filled in plus the `getId`
method implemented if it is for an Eloquent resource. For example:

```php
class Schema extends SchemaProvider
{
    
    protected $resourceType = 'posts';
    
    /**
     * @param App\Post $resource
     * @return string
     */
    public function getId($resource)
    {
        return (string) $resource->getRouteKey();
    }
}
```

> If you are using the [Eloquent adapter](./adapters.md) and decide to use an `id` other than the model's
route key, you must set the `$primaryKey` property on your adapter so that it matches your schema.
For example, if your schema used `$model->getKey()` you would need to set the `$primaryKey` property
on your adapter so that it matched the return value of `$model->getKeyName()`.

## Fields

A resource object’s attributes and its relationships are collectively called its “fields”.

Fields for a resource object must share a common namespace with each other and with type and id.
In other words, a resource can not have an attribute and relationship with the same name,
nor can it have an attribute or relationship named `type` or `id`.

## Attributes

A resource object can contain an `attributes` object containing additional properties of the resource.
Attributes are returned by the `getAttributes()` method on your schema. If you have generated your schema,
this method will already be implemented.

As an example, a schema for a `posts` resource could look like this: 

```php
class Schema extends SchemaProvider
{

    // ...
    
    /**
     * @param App\Post $post
     * @return array
     */
    public function getAttributes($post)
    {
        return [
            'created-at' => $post->created_at->toW3cString(),
            'updated-at' => $post->updated_at->toW3cString(),
            'title' => $post->title,
            'content' => $post->content,
            'slug' => $post->slug,
            'published-at' => $post->published_at ? $post->published_at->toW3cString() : null,
        ];
    }
}
```

The above schema would result in the following resource object:

```json
{
    "type": "posts",
    "id": "1",
    "attributes": {
        "created-at": "2018-01-01T11:00:00+00:00",
        "updated-at": "2018-01-01T12:10:00+00:00",
        "title": "My First Post",
        "content": "...",
        "slug": "my-first-post",
        "published-at": "2018-01-01T12:00:00+00:00"
    }
}
```

## Relationships

A resource object may have a `relationships` key that holds a relationships object. This object describes linkages
to other resource objects. Relationships can either be to-one or to-many. The JSON API spec allows these linkages 
to be described in resource relationships in multiple ways - either through a `links`, `data` or `meta` value, 
or a combination of all three.

> It's worth mentioning again that every PHP class that could be returned as a related object must have a schema
and be defined in your API's `resources` configuration. Otherwise you will get an error when encoding relationships.

A schema defines the relationships that are to be serialized for a resource object in its `getRelationships()`
method.

### Links

A resource relationship can be described with URL links - either a `self` link or a `related` link. The `self`
link provides the URI at which a client can obtain the relationship object. The `related` link provides the URI
for obtaining the related resource object(s).

For example, if our `App\Post` model has a `comments` relationship, its relationship can be described as follows:

```php
class Schema extends SchemaProvider
{
    $resourceType = 'posts';
    
    // ...
    
    public function getRelationships($post, $isPrimary, array $includeRelationships)
    {
        return [
            'comments' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
            ],
        ];
    }
}
```

This would generate the following resource object:

```json
{
    "type": "posts",
    "id": "1",
    "relationships": {
        "comments": {
            "links": {
                "self": "/api/posts/1/relationships/comments",
                "related": "/api/posts/1/comments"
            }
        }
    }
}
```

> These links will only work if you register them when define your API's routing. For `related` links, see 
[Fetching Resources](../fetching/resources.md) and for the `self` link, see
[Fetching Relationships](../fetching/relationships.md).

### Data

Relationship `data` contains the resource identifier for the related resource or resources. A resource identifier
contains the unique combination of a `type` and `id` for the related resources. The JSON API encoder will create
these resource identifiers when a PHP instance is returned for relationship data. For example:

```php
class Schema extends SchemaProvider
{
    $resourceType = 'posts';

    // ...

    public function getRelationships($post, $isPrimary, array $includeRelationships)
    {
        return [
            'author' => [
                self::DATA => function () use ($post) {
                    return $post->createdBy;
                },
            ],
        ];
    }
}
```

This would generate the following resource object:

```json
{
    "type": "posts",
    "id": "1",
    "relationships": {
        "author": {
            "data": {
                "type": "users",
                "id": "123"
            }
        }
    }
}
```

Notice the return values are wrapped in closures. This is so that the *cost* of getting the related object is only
incurred if the data is definitely going to be contained in the encoded JSON API response. For example, the data
may be skipped if the client has requested [sparse fieldsets](../fetching/sparse-fieldsets.md).

If you need to variably include the data, you can use the `SHOW_DATA` option. The following example would only
include the author data if the related resource was to be included in a compound document:

```php
class Schema extends SchemaProvider
{
    $resourceType = 'posts';

    // ...

    public function getRelationships($post, $isPrimary, array $includeRelationships)
    {
        return [
            'author' => [
                self::SHOW_DATA => isset($includeRelationships['author']),
                self::DATA => function () use ($post) {
                    return $post->createdBy;
                },
            ],
        ];
    }
}
```

We do not recommend using `data` for any to-many relationships that could have a large number of related resources.
For example, if our `posts` resource has a `comments` relationship, it would not be sensible to return data for the
related comments because a post could have hundreds of comments. This would result in very slow encoding speed.
Large to-many relations are best represented using `links`.

### Meta

A relationship object can contain a `meta` object, that can contain any information needed about the relationship.
To return meta for a relationship on a resource object:

```php
class Schema extends SchemaProvider
{
    $resourceType = 'posts';
    
    // ...
    
    public function getRelationships($post, $isPrimary, array $includeRelationships)
    {
        return [
            'comments' => [
                self::META => function () use ($post) {
                    return [
                        'total' => $post->comments_count,
                    ];
                },
            ],
        ];
    }
}
```

This would generate the following resource object:

```json
{
    "type": "posts",
    "id": "1",
    "relationships": {
        "comments": {
            "meta": {
                "total": 3
            }
        }
    }
}
```

As with `data`, we wrap the meta in a closure so that the cost of generating it is only incurred if the 
relationship is definitely appearing in the encoded response. This however is optional - i.e. you would not need
to wrap the meta in a closure if there is no cost involved in generating it.

## Combining Data, Links and Meta

A resource relationship can contain any combination of `data`, `links` and `meta` you want. For example, we
use the following as our default relationship to give the client control over how it wants the relationship
included:

```php
class Schema extends SchemaProvider
{
    $resourceType = 'posts';
    
    // ...
    
    public function getRelationships($post, $isPrimary, array $includeRelationships)
    {
        return [
            'author' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['author']),
                self::DATA => function () use ($post) {
                    return $post->createdBy;
                },
            ],
        ];
    }
}
```

Would generate the following resource object:

```json
{
    "type": "posts",
    "id": "1",
    "relationships": {
        "author": {
            "links": {
                "self": "/api/posts/1/relationships/author",
                "related": "/api/posts/1/author"
            },
            "data": {
                "type": "users",
                "id": "123"
            }
        }
    }
}
```

## Links

By default all resource objects will be encoded with their `self` link, e.g.:

```json
{
    "type": "posts",
    "id": "1",
    "attributes": {
        "title": "My First Post",
        "content": "..."
    },
    "links": {
        "self": "/api/posts/1"
    }
}
```

You can change this behaviour by overloading the `getResourceLinks` or `getIncludedResourceLinks` methods.
For example:

```php
class Schema extends SchemaProvider
{
    // ...

    public function getResourceLinks($resource)
    {
        $links = parent::getResourceLinks($resource);
        $links['foo'] = $this->createLink('posts/foo');

        return $links;
    }

}
```

This would result in the following resource object:

```json
{
    "type": "posts",
    "id": "1",
    "attributes": {
        "title": "My First Post",
        "content": "..."
    },
    "links": {
        "self": "/api/posts/1",
        "foo": "/api/posts/foo"
    }
}
```

> The `createLink` method allows you to pass in link meta and set whether the URI is relative to the API or an
absolute path.

If you want to only change the links when the resource is appearing in the `included` section of the JSON API
document, overload the `getIncludedResourceLinks()` method instead.

## Meta

You can add top-level `meta` to your resource object using the `getPrimaryMeta()` or `getInclusionMeta()` methods
on your schema. These are called depending on whether your resource is appearing in either the primary `data`
member of the JSON API document or the `included` member.

For example, the following would add meta to your resource object regardless of whether it is primary data or
included in the document:

```php
class Schema extends SchemaProvider
{
    // ...

    public function getPrimaryMeta($resource)
    {
        return ['foo' => 'bar'];
    }

    public function getInclusionMeta($resource)
    {
        return $this->getPrimaryMeta($resource);
    }

}
```

This would result in the following resource object:

```json
{
    "type": "posts",
    "id": "1",
    "attributes": {
        "title": "My First Post",
        "content": "..."
    },
    "meta": {
        "foo": "bar"
    }
}
```
