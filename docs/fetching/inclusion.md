# Inclusion of Related Resources

## Introduction

This package supports the [inclusion of related resources](http://jsonapi.org/format/1.0/#fetching-includes). 
This allows a client to specify resources related to the primary data that should be included in the response.
The purpose is to allow the client to reduce the number of HTTP requests it needs to make to obtain all the data
it requires.

This package supports:

- Client-specified include paths, via the `include` query parameter.
- Validation of include paths provided by a client.
- Default include paths to use if the client does not specify any paths.
- Automatic translation of include paths to Eloquent eager load paths.

For this feature to work, you will need to:

1. Define the allowed include paths for a resource on its [Validators](../basics/validators.md) class. By
default validators do not allow include paths.
2. Add relationships to the resource [Schema](../basics/schemas.md) and ensure they return data.
3. For Eloquent models, define the translation of any JSON API include paths to eager load paths
on the resource [Adapter](../basics/adapters.md).  

These are all described in this chapter.

## The Include Query Parameter

Related resources are specified by the client using the `include` query parameter. This parameter 
contains a comma separated list of relationship paths that should be included. The response will be a
[compound document](http://jsonapi.org/format/#document-compound-documents) where the primary data of the
request is in the JSON's `data` member, and the related resources are in the `included` member.

For example, if a client is requesting `posts` resources, it can obtain the related `author` and `tags`
resources in the same request:

```http
GET /api/posts?include=author,tags HTTP/1.1
Accept: application/vnd.api+json
``` 

If these include paths are valid, then the client will receive the following response:

```http
HTTP/1.1 200 OK
Content-Type: application/vnd.api+json

{
    "data": [
        {
            "type": "posts",
            "id": "123",
            "attributes": {
                "title": "Hello World",
                "content": "..."
            },
            "relationships": {
                "author": {
                    "data": {
                        "type": "users",
                        "id": "45"
                    },
                    "links": {
                        "self": "/api/posts/123/relationships/author",
                        "related": "/api/posts/123/author"
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
                    ],
                    "links": {
                        "self": "/api/posts/123/relationships/tags",
                        "related": "/api/posts/123/tags"
                    }                
                }
            },
            "links": {
                "self": "/api/posts/123"
            }
        }
    ],
    "included": [
        {
            "type": "users",
            "id": "45",
            "attributes": {
                "name": "John Smith"
            },
            "links": {
                "self": "/api/users/45"
            }
        },
        {
            "type": "tags",
            "id": "1",
            "attributes": {
                "title": "news"
            },
            "links": {
                "self": "/api/tags/1"
            }
        },
        {
            "type": "tags",
            "id": "3",
            "attributes": {
                "title": "world"
            },
            "links": {
                "self": "/api/tags/3"
            }
        }
    ]
}
```

### Nested Paths

Include paths use dot notation to specify nested paths. For example, if our `users` resource had an `address`
relationship, the client could request the following:

```http
GET /api/posts?include=author.address,tags HTTP/1.1
Accept: application/vnd.api+json
``` 

For this request, both the author `users` resource and the user's `addresses` resource would be present in
the `included` member of the JSON document.

### Creating and Updating Resources

You can use include paths when creating resources, for example:

```http
POST /api/posts?include=author HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{...}
```

The same also applies for updating resources, for example:

```http
PATCH /api/posts/123?include=author HTTP/1.1
Content-Type: application/vnd.api+json
Accept: application/vnd.api+json

{...}
```

> For both requests, the include paths refer to what the client wants included in the response.

### Relationship Endpoints

Include paths for a resource also work on relationship endpoints where that resource is the primary
data.

For example, if a `comments` resource had a `post` relationship, we can use include paths for a `posts`
resource when retrieving the related post:

```http
GET /api/comments/456/post?include=author,tags HTTP/1.1
Accept: application/vnd.api+json
```

This request will contain the related post as the primary data, and include the post's author and tags
in the `included` member of the compound document.

When making this request, the include paths are validated by the `posts` validators, not the validators
for the `comments`. This is because the include paths refer to the post's relationships.

## Allowing Include Paths

By default validators generated by this package do **not** allow any include paths. This is because the
Eloquent adapter automatically converts include paths to eager load paths. We therefore expect include
paths to be whitelisted otherwise the client could provide a path that is not a valid model relationship
path.

To allow include paths, list them on your resource's validators class using the `$allowedIncludePaths`
property:

```php
namespace App\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Validation\AbstractValidators;

class Validators extends AbstractValidators
{

    protected $allowedIncludePaths = [
        'author',
        'author.address',
        'tags'
    ];
    
    // ...
}
```

If the client provides an invalid include path, it will receive the following response:

```http
HTTP/1.1 400 Bad Request
Content-Type: application/vnd.api+json

{
    "errors": [
        {
            "title": "Invalid Query Parameter",
            "status": "400",
            "detail": "Include path foo is not allowed.",
            "source": {
                "parameter": "include"
            }
        }
    ]
}
```

## Returning Related Resources

For include paths to work, you must return relationship data in your resource's schema. This
is done in the `getRelationships` method of your schema. For the above example to work,
the posts schema would have to return both the related author and the related tags:

```php
namespace App\JsonApi\Posts;

use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

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
            'tags' => [
                self::SHOW_SELF => true,
                self::SHOW_RELATED => true,
                self::SHOW_DATA => isset($includeRelationships['tags']),
                self::DATA => function () use ($post) {
                    return $post->tags;
                },
            ],
        ];
    }
}
```

In the above, the data is only shown if the relationship is included. Returning the related
record is wrapped in a closure so that we only incur the cost of querying the database if
the relationship is included.

### Nested Paths

If you are allowing nested paths, e.g. `author.address` the `posts` schema only needs
to return the related author. The author's address is returned in the `getRelationships()`
method on the `users` schema.

## Eager Loading

The Eloquent adapter automatically converts JSON API include paths to Eloquent model 
[eager loading](https://laravel.com/docs/eloquent-relationships#eager-loading) paths.
The JSON API path is converted to a camel-case path. For example, the JSON API path
`author.current-address` is converted to the `author.currentAddress` Eloquent path.

If this automatic conversion is not correct for a particular path, you can define the mapping
on your Eloquent adapter using the `$includePaths` property. For example, if a `posts`
resource's `author` relationship was actually the `createdBy` relationship on a post model, we
would need to add the following mapping to the adapter:

```php
namespace App\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;

class Adapter extends AbstractAdapter
{
    protected $includePaths = [
        'author' => 'createdBy',
        'author.current-address' => 'createdBy.currentAddress',
    ];
    
    // ...
}
```

If you do not want a JSON API path to be translated to an Eloquent path, map it to `null`.
For example:

```php
protected $includePaths = [
    'author' => null,
];
```

## Default Include Paths

It is possible to define default include paths to use if the client does not specify any paths.
Default include paths are defined on a resource's schema via the `getIncludePaths()` method.

For example, if we always wanted to include the `author` and `tags` when `posts` resources are
requested as primary data:

```php
namespace App\JsonApi\Posts;

use Neomerx\JsonApi\Schema\SchemaProvider;

class Schema extends SchemaProvider
{

    // ...

    public function getIncludePaths()
    {
        return ['author', 'tags'];
    }
}
```

This would mean that the following request receive a response with `users` and `tags` resources in the
`included` member of the JSON document:

```http
GET /api/posts HTTP/1.1
Accept: application/vnd.api+json
``` 

### Default Path Eager Loading

Default paths are not automatically translated to Eloquent eager load paths. If you are using them,
it is recommended that you also add default eager load paths to your resource adapter.

```php
namespace App\JsonApi\Posts;

use CloudCreativity\LaravelJsonApi\Eloquent\AbstractAdapter;

class Adapter extends AbstractAdapter
{
    protected $defaultWith = ['author', 'tags'];
    
    // ...
}
```

> Note that the default paths here are your model's relationship paths, not the JSON API include paths.
