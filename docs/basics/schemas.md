# Resource Object Schemas

## Introduction

Resource objects are the JSON API representations of your application's domain records (instances of PHP classes).
Every PHP class that can appear in your JSON API documents must have a `Schema` class. This schema defines how
to convert the PHP object into a JSON API resource object.

This package provides an Eloquent schema for rapid development of resources that relate to Eloquent models. However
schemas can be defined for any PHP class by extending a generic schema.

> Note that Eloquent schemas will be deprecated during the `1.0.0-alpha` release series. We therefore recommend
that you use generic schemas for any new schemas that you are creating. Eloquent schemas are described in this
chapter in case you are using them for any existing schemas.

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
even resources that do not have API routes defined for them. This is so that the encoder that creates 
JSON API documents can locate a schema for each PHP class it encounters.

## Generating Schemas

### Eloquent Schemas

To generate an Eloquent schema, use the following command. The generated schema will extend
`CloudCreativity\LaravelJsonApi\Eloquent\AbstractSchema`.


```bash
php artisan make:json-api:schema -e <resource-type> [<api>]
```

> The `-e` option does not need to be included if your API configuration has its `use-eloquent` option set
to `true`.

Eloquent schemas provide a rapid way to develop a JSON API based on Eloquent models. However, they are less efficient
than the generic schemas because they involve an amount of processing of Eloquent models. If performance is your
primary concern, we recommend using generic schemas for your Eloquent models.

> It is also worth pointing out that the Eloquent schema extends from the generic schema. Therefore any methods that
are described on the generic schema can also be implemented on the Eloquent schema.

### Generic Schemas

To generate a generic schema that extends, use the following command. The generated schema will extend
`Neomerx\JsonApi\Schema\SchemaProvider`.

```bash
php artisan make:json-api:schema -N <resource-type> [<api>]
```

> The `-N` option does not need to be included if your API configuration has its `use-eloquent` option set
to `false`.

## Identification

Every JSON API resource object must have a `type` and `id` member. To define the `type`, set the `$resourceType`
property on your schema:

```php
class Schema extends SchemaProvider
{

  protected $resourceType = 'posts';
  
  // ...
}
```

By default, Eloquent schemas will return a model's key as its `id`. If you want to use a different model 
attribute as its JSON API id, set the `$idName` property:

```php
class Schema extends AbstractSchema
{

  protected $resourceType = 'posts';
  
  protected $idName = 'slug';
  
  // ...
}
```  

> Note that the ID must match the ID used in URLs. If you are not using the model key as your JSON API resource
object ID, you must also configure your resource's adapter to use the alternative attribute (e.g. `slug` in the
above example).

If you are using the generic schema you will need to implement the `getId()` method to return the ID for a
resource:

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
        return (string) $resource->getId();
    }
}
```

> The JSON API spec specifies that the `id` of a resource object must be a string.

## Fields

A resource object’s attributes and its relationships are collectively called its “fields”.

Fields for a resource object must share a common namespace with each other and with type and id.
In other words, a resource can not have an attribute and relationship with the same name,
nor can it have an attribute or relationship named type or id.

## Attributes

A resource object can contain an `attributes` object containing additional properties of the resource. 

### Generic Schemas

To generate attributes for a resource on a generic schema, implement the `getAttributes` method.
This method must return an array representing the attributes in the resource object. For example:

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

### Eloquent Schemas

By default the Eloquent schema will serialize attributes that are listed in the `$visible` property of your Eloquent
model. However, this is easy to override by listing the model attributes you want included in your JSON API
resource object by using the `$attributes` property of the schema:

```php
class Schema extends AbstractSchema
{
    
    protected $resourceType = 'posts';
    
    protected $attributes = [
        'title',
        'content',
        'slug',
        'published_at',
    ];
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

Model attributes can be mapped to different resource object attribute fields. On the schema's `$attributes` property,
use the model attribute as the key and the resource object attribute field as the value. For example:

```php
class Schema extends AbstractSchema
{
    
    protected $resourceType = 'posts';
    
    protected $attributes = [
        'title' => 'heading',
        'content',
        'slug',
        'published_at',
    ];
}
```

Would map the model's `title` attribute to the resource's `heading` field, resulting in the following JSON:

```json
{
    "type": "posts",
    "id": "1",
    "attributes": {
        "created-at": "2018-01-01T11:00:00+00:00",
        "updated-at": "2018-01-01T12:10:00+00:00",
        "heading": "My First Post",
        "content": "...",
        "slug": "my-first-post",
        "published-at": "2018-01-01T12:00:00+00:00"
    }
}
```

#### Created, Updated and Deleted Dates

As shown above, the Eloquent schema will always include created and updated dates on the model if they exist,
so you do not need to list them in `$attributes` property. For soft deletes models, it will also include the 
deleted date. To turn this off, set the `$createdAt`, `$updatedAt` and/or `$deletedAt` properties on the 
schema to `false`. For example:

```php
class Schema extends AbstractSchema
{
    // ...
    
    protected $createdAt = false;
    protected $updatedAt = false;
    protected $deletedAt = false;
}
```

#### Attribute Field Names

Note that by default the Eloquent schema uses *hyphenated* attribute field names. This is the default as it is
recommended by the JSON API spec. However, you can change this behaviour by setting the `$hyphenated` attribute
to `false`. If this is false, then the attribute keys will be either snake case or camel case depending on the
default for the model class. 

#### Date Formats

By default the Eloquent schema uses the W3C format when serializing PHP `DateTime` values. If you want to 
change this, set the `$dateFormat` attribute:

```php
class Schema extends AbstractSchema
{
  // ...

  $dateFormat = 'Y-m-d H:i:s
}
```

## Relationships

A resource object may have a `relationships` key that holds a relationships object. This object describes linkages
to other resource objects. Relationships can either be to-one or to-many. The JSON API spec allows these linkages 
to be described in resource relationships in multiple ways - either through a `data`, `links` or `meta` value, 
or a combination of all three.

> It's worth mentioning again that every PHP class that could be returned as a related object must have a schema
and be defined in your API's `resources` configuration. Otherwise you will get an error when encoding relationships.

### Eloquent Schema

The Eloquent schema provides a generic implementation for relationships. This will return `links` for relationships
and will also include `data` resource is part of a compound document - i.e. if the client has requested
[inclusion of related resource](../fetching/inclusion.md).

To add relationships to your resource, add the model relationship names to the `$relationships` property of your
Eloquent schema. For example:

```php
class Schema extends AbstractSchema
{
  // ...

  $relationships = ['author', 'comments'];
}
```

This would result in the following resource object:

```json
{
    "type": "posts",
    "id": "1",
    "relationships": {
        "author": {
            "links": {
                "self": "/api/posts/1/relationships/author",
                "related": "/api/posts/1/author"
            }
        },
        "comments": {
            "links": {
                "self": "/api/posts/1/relationships/comments",
                "related": "/api/posts/1/comments"
            }
        }
    }
}
```

If the client requested that the `author` relationship was included, the generated resource object would look like
this:

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
        },
        "comments": {
            "links": {
                "self": "/api/posts/1/relationships/comments",
                "related": "/api/posts/1/comments"
            }
        }
    }
}
```

If you need to use a JSON API field name that is different from the model relationship name, you can define this
in the `$relationships` property on your schema. For example, if the post's author was stored as the `createdBy`
Eloquent relationship:

```php
class Schema extends AbstractSchema
{
  // ...

  $relationships = [
    'createdBy' => 'author',
    'comments',
  ];
}
```

> We have chosen to always include `links` and only include `data` if the resource is in a compound document as in
our experience this is a sensible default. If you want a different strategy, overload the `getRelationships` method
on your Eloquent schema and follow the instructions for generic schemas below.

### Generic Schema

A schema defines the relationships that are to be serialized for a resource object in its `getRelationships` method.

#### Links

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

> These links will only work if you implement them in your API. For `related` links, see 
[Fetching Resources](../fetching/resources.md) and for the `self` link, see
[Fetching Relationships](../fetching/relationships.md).

#### Data

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
Large to many relations are best represented using `links`.

#### Meta

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
                        'total' => $post->comments()->count(),
                        'updated-at' =>
                            $post->getMostRecentComment()->created_at->format('Y-m-d H:i:s'),
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
                "total": 3,
                "updated-at": "2018-01-01 12:10:00"
            }
        }
    }
}
```

As with `data`, we wrap the meta in a closure so that the cost of generating it is only incurred if the 
relationship is definitely appearing in the encoded response. This however is optional - i.e. you would not need
to wrap the meta in a closure if there is no cost involved in generating it.

### Combining Data, Links and Meta

A resource relationship can contain any combination of `data`, `links` and `meta` you want. For example:

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
                self::META => [
                    'foo' => 'bar',
                ],
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
            },
            "meta": {
                "foo": "bar"
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
document, overload the `getIncludedResourceLinks` method instead.

## Meta

You can add top-level `meta` to your resource object using the `getPrimaryMeta` or `getInclusionMeta` methods
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
