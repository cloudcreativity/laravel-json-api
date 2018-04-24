# Resource Object Schemas

Resource objects are the JSON API representations of your application's domain records (instances of PHP classes).
Every PHP class that can appear in your JSON API documents must have a `Schema` class. This schema defines how
to convert the PHP object into a JSON API resource object.

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

This package provides Artisan generators to create schemas either for Eloquent models or generic schemas for
other PHP classes. Use any of the following commands:

```bash
# posts schema in your default api
# will use your `use-eloquent` config option to determine what schema to generate
$ php artisan make:json-api:schema posts

# posts schema in your v1 api
$ php artisan make:json-api:schema posts v1

# posts schema, forced to be an Eloquent schema...
$ php artisan make:json-api:schema posts -e

# posts schema, forced to be the generic schema...
$ php artisan make:json-api:schema posts -N
```

The generated schema will be placed in the namespace according to your API's namespace configuration.

## Identification

Every JSON API resource object must have a `type` and `id` member. To define the `type`, set the `$resourceType`
property on your schema:

```php
namespace App\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractSchema;

class Schema extends AbstractSchema
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

To generate attributes for a resource on a generic (non-Eloquent) schema, implement the `getAttributes` method.
This method must return an array representing the attributes in the resource object. For example:

```php
use Neomerx\JsonApi\Schema\SchemaProvider;

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
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'slug' => $post->getSlug(),
        ];
    }
}
```

### Eloquent Schemas

By default the Eloquent schema will serialize all fillable attributes to the attributes object on a resource object.
However, this is easy to override by listing the model attributes you want included in your JSON API resource object
by using the `$attributes` property of the schema:

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

A schema defines the relationships that are to be serialized for a resource object in its `getRelationships` method.

### Data

Relationship `data` contains the resource identifier for the related resource or resources. A resource identifier
contains the unique combination of a `type` and `id` for the related resources. The JSON API encoder will create 
these resource identifiers when a PHP instance is returned for relationship data.

This is best illustrated with an example. If an `App\Post` model has an `author` relationship that returns an 
`App\User` model, and a `tags` relationship that returns a collection of `App\Tag` models. With this
JSON API config:

```php
'resources' => [
    'posts' => App\Post::class,
    'users' => App\User::class,
    'tags' => App\Tag::class,
]

// ...
```

We would implement the `getRelationships` method on the posts schema as follows:

```php
class Schema extends AbstractSchema
{
    $resourceType = 'posts';
    
    // ...
    
    public function getRelationships($post, $isPrimary, array $includeRelationships)
    {
        return [
            'author' => [
                self::DATA => $post->author,
            ],
            'tags' => [
                self::DATA => $post->tags,
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
        },
        "tags": {
            "data": [
                {
                    "type": "tags",
                    "id": "3"
                },
                {
                    "type": "tags",
                    "id": "6"
                }
            ]
        }
    }
}
```

### Links

A resource relationship can be described with URL links - either a `self` link or a `related` link. The `self`
link provides the URI at which a client can obtain the relationship object. The `related` link provides the URI
for obtaining the related resource object(s).

For example, if our `App\Post` model has a `comments` relationship, its relationship can be described as follows:

```php
class Schema extends AbstractSchema
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
                "self": "/api/v1/posts/1/relationships/comments",
                "related": "/api/v1/posts/1/comments"
            }
        }
    }
}
```

> These links will only work if you implement them in your API. For `related` links, see 
[Fetching Resources](../fetching-data/fetching-resources.md) and for the `self` link, see
[Fetching Relationships](../fetching-data/fetching-relationships.md).

### Meta

A relationship object can contain a `meta` object, that can contain any information needed about the relationship.
To return meta for a relationship on a resource object:

```php
class Schema extends AbstractSchema
{
    $resourceType = 'posts';
    
    // ...
    
    public function getRelationships($post, $isPrimary, array $includeRelationships)
    {
        return [
            'comments' => [
                self::META => [
                    'total' => $post->comments()->count(),
                    'updated-at' => 
                        $post->getMostRecentComment()->created_at->format('Y-m-d H:i:s'),
                ],
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

### Combining Data, Links and Meta

A resource relationship can contain any combination of `data`, `links` and `meta` you want. For example:

```php
class Schema extends AbstractSchema
{
    $resourceType = 'posts';
    
    // ...
    
    public function getRelationships($post, $isPrimary, array $includeRelationships)
    {
        return [
            'author' => [
                self::DATA => $post->author,
                self::SHOW_SELF => true,
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
            "data": {
                "type": "users",
                "id": "123"
            },
            "links": {
                "self": "/api/v1/posts/1/relationships/author"
            },
            "meta": {
                "foo": "bar"
            }
        }
    }
}
```
